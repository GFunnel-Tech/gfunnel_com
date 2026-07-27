<?php defined('BX_DOL') or die('hack attempt');
/**
 * GFunnel — automatic XML sitemap generator.
 *
 * Builds sitemap.xml from live data, so every new page appears automatically:
 *  - system/builder pages from `sys_objects_page` (guest-visible, indexable,
 *    param-less pages of enabled modules only);
 *  - content entries of every enabled module that exposes a public view page
 *    (URI_VIEW_ENTRY + TABLE_ENTRIES in the module config) — posts, events,
 *    products, discussions, courses, profiles, listings, goals, jobs, etc.
 *    New modules are picked up automatically, no code change needed;
 *  - the GFunnel Application Hub: the /applications + /marketplace/applications
 *    landing pages and one /application/<slug> page per app in the
 *    gf_directory_apps mirror (every synced app is indexable automatically);
 *  - extra hand-maintained URLs from the `gf_sitemap_extra_urls` setting.
 *
 * Only public content is listed: entry `status`/`status_admin` must be
 * 'active', per-entry privacy (`allow_view_to`) must be Public, and for
 * profile-based modules the linked `sys_profiles` row must be active.
 *
 * URLs are built through BxDolPermalinks, so SEO links (sys_seo_links slugs)
 * are used when enabled — existing slugs are bulk-prefetched, missing ones
 * are created on the fly exactly like the front-end does.
 *
 * Output is cached in cache/ and served by /sitemap.php (rewritten from
 * /sitemap.xml). Above 45k URLs it automatically splits into a sitemap
 * index + numbered chunk files (/sitemap-N.xml), per the sitemaps.org spec.
 *
 * Optional settings (sys_options), all with sensible defaults:
 *  - gf_sitemap_disable          'on' turns /sitemap.xml into a 404
 *  - gf_sitemap_ttl              max cache age in seconds (default 21600 = 6h)
 *  - gf_sitemap_modules_exclude  comma-separated module names to skip,
 *                                added to the built-in default exclude list
 *  - gf_sitemap_modules_include  comma-separated module names to re-include
 *                                even if excluded by default (e.g. bx_timeline)
 *  - gf_sitemap_pages_exclude    comma-separated system page URIs to skip
 *                                (added to the built-in deny list)
 *  - gf_sitemap_max_per_module   cap of entries per module, 0 = unlimited
 *  - gf_sitemap_extra_urls       one URL per line: "url [changefreq] [priority]"
 */
class GfSiteMapGenerator
{
    const CHUNK_SIZE = 45000;      // max URLs per sitemap file (spec allows 50k)
    const QUERY_PAGE_SIZE = 5000;  // rows fetched per DB round-trip

    protected $sCacheDir;
    protected $sCachePrefix = 'gf_sitemap_';
    protected $aEnabledModules;

    // streaming writer state — URLs are flushed to chunk files as they are
    // produced, so a full rebuild never holds the whole URL set in memory.
    protected $aSeenLoc = [];      // dedupe set: loc => true (first occurrence wins)
    protected $rChunkHandle = null; // handle of the chunk file currently open
    protected $sChunkTmp = '';      // temp path of the chunk being written
    protected $sChunkFinal = '';    // final path it is renamed to on close
    protected $iChunkIndex = 0;     // number of chunks opened so far (1-based)
    protected $iChunkRows = 0;      // URLs written into the current chunk
    protected $iTotalUrls = 0;      // total URLs written across all chunks
    protected $bStreamError = false;// sticky: a write/rotation failed mid-stream

    /**
     * Modules skipped by default: private, ephemeral or thin-content types.
     * Extend with gf_sitemap_modules_exclude, re-include with
     * gf_sitemap_modules_include.
     */
    protected $aModulesExcludeDefault = [
        'bx_convos',            // private conversations
        'bx_tasks',             // internal tasks
        'bx_timeline',          // timeline items — thin duplicates of source content
        'bx_messenger',         // chat lots
        'bx_invites',
        'bx_attendant',
        'bx_quoteofday',
        'bx_donations',
        'bx_stripe_connect',
        'bx_stream',            // live streams are ephemeral
    ];

    /**
     * System page URIs never included (auth, utility and param-only pages).
     * Extend with the gf_sitemap_pages_exclude setting.
     */
    protected $aPagesExcludeDefault = [
        'login', 'logout', 'create-account', 'forgot-password', 'confirm-email',
        'acl-view', 'cmts-view', 'goodbye', 'error', 'access-denied',
        'page-not-found', 'search', 'search-results', 'search-extended',
        'workspaces', 'invites',
    ];

    /** URI prefixes/suffixes of per-user or action pages. */
    protected $aPagesExcludePrefixes = ['create-', 'edit-', 'add-', 'manage-', 'account-', 'settings', 'studio', 'invite-'];
    protected $aPagesExcludeSuffixes = ['-manage', '-search', '-edit', '-add', '-favorites', '-favorite', '-settings', '-manager'];

    public static function getInstance()
    {
        if (!isset($GLOBALS['gfSiteMapGenerator']))
            $GLOBALS['gfSiteMapGenerator'] = new self();
        return $GLOBALS['gfSiteMapGenerator'];
    }

    protected function __construct()
    {
        $this->sCacheDir = rtrim(BX_DIRECTORY_PATH_CACHE, '/') . '/';
    }

    public function isEnabled()
    {
        return getParam('gf_sitemap_disable') != 'on';
    }

    public function getTtl()
    {
        $i = (int)getParam('gf_sitemap_ttl');
        return $i > 0 ? $i : 21600;
    }

    public function getMetaFile()
    {
        return $this->sCacheDir . $this->sCachePrefix . 'meta.json';
    }

    public function getChunkFile($iChunk)
    {
        return $this->sCacheDir . $this->sCachePrefix . (int)$iChunk . '.xml';
    }

    public function getIndexFile()
    {
        return $this->sCacheDir . $this->sCachePrefix . 'index.xml';
    }

    public function getLockFile()
    {
        return $this->sCacheDir . $this->sCachePrefix . 'generate.lock';
    }

    /**
     * Cache metadata, validated against the files it describes —
     * returns false when any advertised file is missing (forces a rebuild).
     */
    public function getMeta()
    {
        $sFile = $this->getMetaFile();
        if (!file_exists($sFile))
            return false;

        $a = json_decode(file_get_contents($sFile), true);
        if (!is_array($a) || empty($a['chunks']))
            return false;

        for ($i = 1; $i <= (int)$a['chunks']; $i++)
            if (!file_exists($this->getChunkFile($i)))
                return false;
        if ((int)$a['chunks'] > 1 && !file_exists($this->getIndexFile()))
            return false;

        return $a;
    }

    public function isStale()
    {
        $aMeta = $this->getMeta();
        if (!$aMeta)
            return true;
        return (time() - (int)$aMeta['generated']) > $this->getTtl();
    }

    /** Remove all generated files (used by the uninstaller). */
    public function clearCache()
    {
        @unlink($this->getMetaFile());
        @unlink($this->getIndexFile());
        @unlink($this->getLockFile());
        for ($i = 1; file_exists($this->getChunkFile($i)); $i++)
            @unlink($this->getChunkFile($i));
    }

    /**
     * Serve /sitemap.xml (or a chunk) over HTTP: rebuild when stale, then
     * stream the cached file. Never blocks behind another rebuild — a stale
     * copy is served as-is, and with no copy at all a 503 + Retry-After is
     * returned so crawlers come back once the winning rebuild finishes.
     * @param $mixedChunk falsy for the index/single file, or chunk number.
     */
    public function serve($mixedChunk = false)
    {
        if (!$this->isEnabled()) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        if ($this->isStale())
            $this->generate(); // no-op if another process holds the lock

        $aMeta = $this->getMeta();
        if (!$aMeta) {
            header('HTTP/1.0 503 Service Unavailable');
            header('Retry-After: 120');
            exit;
        }

        $iChunk = (int)$mixedChunk;
        if ($iChunk < 1)
            $sFile = (int)$aMeta['chunks'] > 1 ? $this->getIndexFile() : $this->getChunkFile(1);
        else
            $sFile = $iChunk <= (int)$aMeta['chunks'] ? $this->getChunkFile($iChunk) : '';

        if (!$sFile || !file_exists($sFile)) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex'); // the sitemap itself is not a search result
        header('Cache-Control: public, max-age=' . min($this->getTtl(), 3600));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int)$aMeta['generated']) . ' GMT');
        readfile($sFile);
        exit;
    }

    /**
     * Rebuild all sitemap files from live data. Self-locking: concurrent
     * calls (cron + web + admin) never interleave — the losers return false
     * immediately and can keep serving the previous copy.
     * @return array stats ['urls' => int, 'chunks' => int, 'duration' => float] or false.
     */
    public function generate()
    {
        $rLock = @fopen($this->getLockFile(), 'c');
        if (!$rLock) {
            bx_log('sys_cron_jobs', 'gf_sitemap ERROR: cannot create lock file — is ' . $this->sCacheDir . ' writable?');
            return false;
        }
        if (!flock($rLock, LOCK_EX | LOCK_NB)) {
            fclose($rLock); // another process is already generating
            return false;
        }

        // a full rebuild can be slow on big sites — never die halfway through
        @set_time_limit(0);
        ignore_user_abort(true);

        try {
            $mixedResult = $this->doGenerate();
        } finally {
            flock($rLock, LOCK_UN);
            fclose($rLock);
        }

        return $mixedResult;
    }

    protected function doGenerate()
    {
        $fStart = microtime(true);

        // Stream URLs straight to chunk files as they are produced. Sources are
        // walked in priority order (root, system pages, module content, extras)
        // and emitUrl() dedupes by loc — first occurrence wins — so the only
        // thing kept in memory is the lightweight seen-loc set, never the full
        // URL set (which on a large site would exhaust PHP's memory limit).
        $this->streamStart();

        // 1. site root (marketing home page)
        $this->emitUrl(['loc' => BX_DOL_URL_ROOT, 'changefreq' => 'daily', 'priority' => '1.0']);

        // 2. system/builder pages, 3. module content, 4. app directory,
        //    5. hand-maintained extras
        $this->collectSystemPages();
        $this->collectModuleEntries();
        $this->collectDirectoryApps();
        $this->collectExtraUrls();

        $aStream = $this->streamFinish();
        if ($aStream === false)
            return false;

        $iChunks = $aStream['chunks'];

        // write index file when split
        if ($iChunks > 1 && !$this->writeCacheFile($this->getIndexFile(), $this->renderIndex($iChunks)))
            return false;

        // remove leftover chunk files from a previous, larger run
        for ($i = $iChunks + 1; file_exists($this->getChunkFile($i)); $i++)
            @unlink($this->getChunkFile($i));

        $aMeta = [
            'generated' => time(),
            'urls' => $aStream['urls'],
            'chunks' => $iChunks,
            'duration' => round(microtime(true) - $fStart, 3),
        ];
        if (!$this->writeCacheFile($this->getMetaFile(), json_encode($aMeta)))
            return false;

        bx_log('sys_cron_jobs', 'gf_sitemap generated: ' . $aMeta['urls'] . ' urls, ' . $aMeta['chunks'] . ' file(s), ' . $aMeta['duration'] . 's');

        return $aMeta;
    }

    // ------------------------------------------------------------ sources

    /**
     * Guest-visible, indexable, param-less pages from `sys_objects_page`.
     */
    protected function collectSystemPages()
    {
        $oDb = BxDolDb::getInstance();
        $oPermalinks = BxDolPermalinks::getInstance();

        $aEnabledModules = $this->getEnabledModules();

        // URIs requiring params (view/edit/author/context/...) — every value
        // under a URI_* key in any module config needs an &id or &profile_id
        $aParamUris = [];
        foreach ($aEnabledModules as $sModule => $aModule) {
            $oModule = $this->getModuleInstance($sModule);
            if (!$oModule || empty($oModule->_oConfig->CNF) || !is_array($oModule->_oConfig->CNF))
                continue;
            foreach ($oModule->_oConfig->CNF as $sKey => $mixedValue)
                if (0 === strpos($sKey, 'URI_') && is_string($mixedValue) && '' !== $mixedValue)
                    $aParamUris[$mixedValue] = true;
        }

        $aExclude = array_flip($this->aPagesExcludeDefault);
        foreach (preg_split('/\s*,\s*/', (string)getParam('gf_sitemap_pages_exclude'), -1, PREG_SPLIT_NO_EMPTY) as $sUri)
            $aExclude[$sUri] = true;

        $aPages = $oDb->getAll("SELECT `uri`, `module`, `visible_for_levels`, `meta_robots` FROM `sys_objects_page`");
        if (!is_array($aPages))
            return;

        $aDone = [];
        foreach ($aPages as $aPage) {
            $sUri = (string)$aPage['uri'];

            if ('' === $sUri || isset($aDone[$sUri]) || isset($aExclude[$sUri]) || isset($aParamUris[$sUri]))
                continue;
            if ('system' != $aPage['module'] && !isset($aEnabledModules[$aPage['module']]))
                continue;
            if (!((int)$aPage['visible_for_levels'] & 1)) // level 1 = Non-member (guests)
                continue;
            if (!empty($aPage['meta_robots']) && false !== stripos($aPage['meta_robots'], 'noindex'))
                continue;

            $bSkip = false;
            foreach ($this->aPagesExcludePrefixes as $s)
                if (0 === strpos($sUri, $s)) { $bSkip = true; break; }
            if (!$bSkip)
                foreach ($this->aPagesExcludeSuffixes as $s)
                    if (strlen($sUri) > strlen($s) && substr($sUri, -strlen($s)) == $s) { $bSkip = true; break; }
            if ($bSkip)
                continue;

            $aDone[$sUri] = true;
            $this->emitUrl([
                'loc' => BX_DOL_URL_ROOT . $oPermalinks->permalink('page.php?i=' . $sUri),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);
        }
    }

    /**
     * Public content entries of every enabled module with a view page.
     */
    protected function collectModuleEntries()
    {
        $oDb = BxDolDb::getInstance();
        $oPermalinks = BxDolPermalinks::getInstance();

        $aExclude = $this->getExcludedModules();
        $iMaxPerModule = (int)getParam('gf_sitemap_max_per_module');

        // modules whose entries are profiles (must have an active sys_profiles row)
        $aProfileTypes = array_flip((array)$oDb->getColumn("SELECT DISTINCT `type` FROM `sys_profiles`"));

        foreach ($this->getEnabledModules() as $sModule => $aModule) {
            if (isset($aExclude[$sModule]))
                continue;

            $oModule = $this->getModuleInstance($sModule);
            if (!$oModule || empty($oModule->_oConfig->CNF) || !is_array($oModule->_oConfig->CNF))
                continue;

            $CNF = $oModule->_oConfig->CNF;
            if (empty($CNF['URI_VIEW_ENTRY']) || empty($CNF['TABLE_ENTRIES']))
                continue;

            try {
                $aFields = $oDb->getFields($CNF['TABLE_ENTRIES']);
            } catch (Exception $e) {
                continue;
            }
            if (empty($aFields['original']))
                continue;
            $aColumns = array_flip($aFields['original']);

            $sFieldId = $this->resolveField($CNF, $aColumns, 'FIELD_ID', 'id');
            if ('' === $sFieldId)
                continue;

            $sFieldAdded = $this->resolveField($CNF, $aColumns, 'FIELD_ADDED', 'added');
            $sFieldChanged = $this->resolveField($CNF, $aColumns, 'FIELD_CHANGED', 'changed');

            // visibility conditions — apply every filter the table supports
            $aWhere = ['1'];
            if ('' !== ($sField = $this->resolveField($CNF, $aColumns, 'FIELD_STATUS', 'status')))
                $aWhere[] = "`t`.`" . $sField . "` = 'active'";
            if ('' !== ($sField = $this->resolveField($CNF, $aColumns, 'FIELD_STATUS_ADMIN', 'status_admin')))
                $aWhere[] = "`t`.`" . $sField . "` = 'active'";
            if ('' !== ($sField = $this->resolveField($CNF, $aColumns, 'FIELD_ALLOW_VIEW_TO', 'allow_view_to')))
                $aWhere[] = "`t`.`" . $sField . "` = '" . BX_DOL_PG_ALL . "'"; // Public only

            $sJoin = '';
            if (isset($aProfileTypes[$sModule])) {
                $sJoin = " INNER JOIN `sys_profiles` AS `p` ON `p`.`type` = '" . $sModule . "' AND `p`.`content_id` = `t`.`" . $sFieldId . "`";
                $aWhere[] = "`p`.`status` = 'active'";
            }

            $sSelect = "`t`.`" . $sFieldId . "` AS `id`"
                . ($sFieldAdded ? ", `t`.`" . $sFieldAdded . "` AS `added`" : "")
                . ($sFieldChanged ? ", `t`.`" . $sFieldChanged . "` AS `changed`" : "");

            $sQuery = "SELECT " . $sSelect . " FROM `" . $CNF['TABLE_ENTRIES'] . "` AS `t`" . $sJoin
                . " WHERE " . implode(' AND ', $aWhere)
                . " ORDER BY `t`.`" . $sFieldId . "` ASC";

            $aSeoMap = $this->getSeoLinksMap($sModule, $CNF['URI_VIEW_ENTRY']);
            $sSeoBase = $this->getSeoPageUri($CNF['URI_VIEW_ENTRY']);

            $iCollected = 0;
            for ($iFrom = 0; ; $iFrom += self::QUERY_PAGE_SIZE) {
                try {
                    $aRows = $oDb->getAll($sQuery . " LIMIT " . $iFrom . ", " . self::QUERY_PAGE_SIZE);
                } catch (Exception $e) {
                    break;
                }
                if (empty($aRows) || !is_array($aRows))
                    break;

                foreach ($aRows as $aRow) {
                    if ($iMaxPerModule > 0 && $iCollected >= $iMaxPerModule)
                        break 2;

                    if (false !== $aSeoMap && isset($aSeoMap[$aRow['id']]))
                        $sLoc = BX_DOL_URL_ROOT . $sSeoBase . '/' . $aSeoMap[$aRow['id']];
                    else // creates the SEO slug on the fly, or falls back to a standard permalink
                        $sLoc = BX_DOL_URL_ROOT . $oPermalinks->permalink('page.php?i=' . $CNF['URI_VIEW_ENTRY'] . '&id=' . $aRow['id']);

                    $aUrl = [
                        'loc' => $sLoc,
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];

                    $iLastMod = max((int)($aRow['changed'] ?? 0), (int)($aRow['added'] ?? 0));
                    if ($iLastMod > 0)
                        $aUrl['lastmod'] = gmdate('Y-m-d\TH:i:s\Z', $iLastMod);

                    $this->emitUrl($aUrl);
                    $iCollected++;
                }

                if (count($aRows) < self::QUERY_PAGE_SIZE)
                    break;
            }
        }
    }

    /**
     * Hand-maintained URLs from gf_sitemap_extra_urls — one per line,
     * "url [changefreq] [priority]", relative to site root or absolute.
     */
    protected function collectExtraUrls()
    {
        $sParam = trim((string)getParam('gf_sitemap_extra_urls'));
        if ('' === $sParam)
            return;

        foreach (preg_split('/[\r\n]+/', $sParam, -1, PREG_SPLIT_NO_EMPTY) as $sLine) {
            $aParts = preg_split('/\s+/', trim($sLine));
            if (empty($aParts[0]))
                continue;

            $aUrl = ['loc' => bx_absolute_url($aParts[0]), 'changefreq' => 'weekly', 'priority' => '0.5'];
            if (!empty($aParts[1]) && in_array($aParts[1], ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']))
                $aUrl['changefreq'] = $aParts[1];
            if (isset($aParts[2]) && is_numeric($aParts[2]))
                $aUrl['priority'] = sprintf('%.1f', min(1, max(0, (float)$aParts[2])));

            $this->emitUrl($aUrl);
        }
    }

    // ------------------------------------------------------------ helpers

    /**
     * Built-in exclude list + gf_sitemap_modules_exclude,
     * minus gf_sitemap_modules_include. Additive on purpose: a custom
     * exclude never silently re-includes private defaults like bx_convos.
     */
    protected function getExcludedModules()
    {
        $aExclude = array_flip($this->aModulesExcludeDefault);
        foreach (preg_split('/\s*,\s*/', (string)getParam('gf_sitemap_modules_exclude'), -1, PREG_SPLIT_NO_EMPTY) as $s)
            $aExclude[$s] = true;
        foreach (preg_split('/\s*,\s*/', (string)getParam('gf_sitemap_modules_include'), -1, PREG_SPLIT_NO_EMPTY) as $s)
            unset($aExclude[$s]);
        return $aExclude;
    }

    /**
     * Pick the CNF-declared column when it exists in the table,
     * else the conventional column name, else '' (filter not applicable).
     */
    protected function resolveField($CNF, $aColumns, $sKey, $sDefault)
    {
        if (!empty($CNF[$sKey]) && is_string($CNF[$sKey]) && isset($aColumns[$CNF[$sKey]]))
            return $CNF[$sKey];
        return isset($aColumns[$sDefault]) ? $sDefault : '';
    }

    protected function getEnabledModules()
    {
        if (isset($this->aEnabledModules))
            return $this->aEnabledModules;

        $this->aEnabledModules = [];
        $aModules = BxDolModuleQuery::getInstance()->getModulesBy(['type' => 'modules', 'active' => 1]);
        foreach ((array)$aModules as $aModule)
            $this->aEnabledModules[$aModule['name']] = $aModule;

        return $this->aEnabledModules;
    }

    protected function getModuleInstance($sModule)
    {
        try {
            return BxDolModule::getInstance($sModule);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Bulk-prefetch existing SEO slugs (id => slug) for a module view page.
     * Returns false when SEO links are disabled site-wide.
     */
    protected function getSeoLinksMap($sModule, $sPageUri)
    {
        if (!getParam('permalinks_seo_links'))
            return false;

        $aRows = BxDolDb::getInstance()->getPairs(
            "SELECT `param_value`, `uri` FROM `sys_seo_links` WHERE `module` = :module AND `page_uri` = :page_uri AND `param_name` = 'id'",
            'param_value', 'uri',
            ['module' => $sModule, 'page_uri' => $sPageUri]
        );

        return is_array($aRows) ? $aRows : [];
    }

    /**
     * GFunnel Application Hub: the directory landing pages plus one indexable
     * page per app (/application/<slug>), read from the gf_directory_apps
     * mirror. Because pages are routed by slug, every synced app already has a
     * live page — this just advertises them to crawlers. Emits nothing if the
     * feature is off (gf_applications='off') or the mirror table is absent.
     */
    protected function collectDirectoryApps()
    {
        if (getParam('gf_applications') == 'off')
            return;

        $oDb = BxDolDb::getInstance();
        if (!$oDb->getOne("SHOW TABLES LIKE 'gf_directory_apps'"))
            return;

        // Landing pages.
        $this->emitUrl(['loc' => BX_DOL_URL_ROOT . 'applications', 'changefreq' => 'daily', 'priority' => '0.9']);
        $this->emitUrl(['loc' => BX_DOL_URL_ROOT . 'marketplace/applications', 'changefreq' => 'daily', 'priority' => '0.8']);

        // One URL per app, paged so a large catalog never sits in memory at once.
        $iFrom = 0;
        while (true) {
            $aRows = $oDb->getAll("SELECT `slug`, `id`, `synced_at` FROM `gf_directory_apps` ORDER BY `is_featured` DESC, `name` LIMIT " . (int)$iFrom . ", " . self::QUERY_PAGE_SIZE);
            if (!is_array($aRows) || empty($aRows))
                break;
            foreach ($aRows as $a) {
                $sSlug = trim((string)$a['slug']);
                if ($sSlug === '') $sSlug = trim((string)$a['id']);
                if ($sSlug === '') continue;
                $aUrl = ['loc' => BX_DOL_URL_ROOT . 'application/' . rawurlencode($sSlug), 'changefreq' => 'weekly', 'priority' => '0.6'];
                if (!empty($a['synced_at']) && ($iT = strtotime((string)$a['synced_at'])))
                    $aUrl['lastmod'] = date('Y-m-d', $iT);
                $this->emitUrl($aUrl);
            }
            if (count($aRows) < self::QUERY_PAGE_SIZE)
                break;
            $iFrom += self::QUERY_PAGE_SIZE;
        }
    }

    /**
     * View page URI with the SEO URI rewrite map applied
     * (mirrors BxDolPage::transformSeoLink).
     */
    protected function getSeoPageUri($sPageUri)
    {
        $aRewrites = BxDolPageQuery::getSeoUriRewrites();
        return isset($aRewrites[$sPageUri]) ? $aRewrites[$sPageUri] : $sPageUri;
    }

    protected function renderUrlSetHeader()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    }

    protected function renderUrlSetFooter()
    {
        return '</urlset>' . "\n";
    }

    protected function renderUrl($aUrl)
    {
        $s = "<url><loc>" . htmlspecialchars($aUrl['loc'], ENT_QUOTES | ENT_XML1) . "</loc>";
        if (!empty($aUrl['lastmod']))
            $s .= "<lastmod>" . $aUrl['lastmod'] . "</lastmod>";
        if (!empty($aUrl['changefreq']))
            $s .= "<changefreq>" . $aUrl['changefreq'] . "</changefreq>";
        if (!empty($aUrl['priority']))
            $s .= "<priority>" . $aUrl['priority'] . "</priority>";
        return $s . "</url>\n";
    }

    // ---------------------------------------------------- streaming writer

    /** Reset streaming state before a rebuild. */
    protected function streamStart()
    {
        $this->aSeenLoc = [];
        $this->rChunkHandle = null;
        $this->sChunkTmp = '';
        $this->sChunkFinal = '';
        $this->iChunkIndex = 0;
        $this->iChunkRows = 0;
        $this->iTotalUrls = 0;
        $this->bStreamError = false;
    }

    /**
     * Emit one URL into the sitemap stream: dedupe by loc (first occurrence
     * wins), rotate to a new chunk file once CHUNK_SIZE is reached, and write
     * the entry straight to disk so it never has to be held in memory.
     */
    protected function emitUrl(array $aUrl)
    {
        if ($this->bStreamError)
            return;
        if (empty($aUrl['loc']) || isset($this->aSeenLoc[$aUrl['loc']]))
            return;
        $this->aSeenLoc[$aUrl['loc']] = true;

        if ($this->rChunkHandle === null || $this->iChunkRows >= self::CHUNK_SIZE)
            if (!$this->openNextChunk())
                return;

        if (false === @fwrite($this->rChunkHandle, $this->renderUrl($aUrl))) {
            bx_log('sys_cron_jobs', 'gf_sitemap ERROR: cannot write ' . $this->sChunkTmp . ' — check disk space');
            $this->bStreamError = true;
            return;
        }

        $this->iChunkRows++;
        $this->iTotalUrls++;
    }

    /** Close the current chunk (if any) and open the next one, header written. */
    protected function openNextChunk()
    {
        if ($this->rChunkHandle !== null && !$this->closeChunk())
            return false;

        $this->iChunkIndex++;
        $this->iChunkRows = 0;

        $sFinal = $this->getChunkFile($this->iChunkIndex);
        $sTmp = $sFinal . '.tmp.' . getmypid();

        $rHandle = @fopen($sTmp, 'w');
        if (!$rHandle || false === @fwrite($rHandle, $this->renderUrlSetHeader())) {
            if ($rHandle)
                @fclose($rHandle);
            @unlink($sTmp);
            bx_log('sys_cron_jobs', 'gf_sitemap ERROR: cannot open ' . $sTmp . ' — check ' . $this->sCacheDir . ' permissions/disk space');
            $this->bStreamError = true;
            return false;
        }

        $this->rChunkHandle = $rHandle;
        $this->sChunkTmp = $sTmp;
        $this->sChunkFinal = $sFinal;
        return true;
    }

    /** Write the footer, close the temp file and atomically rename it into place. */
    protected function closeChunk()
    {
        if ($this->rChunkHandle === null)
            return true;

        $bOk = (false !== @fwrite($this->rChunkHandle, $this->renderUrlSetFooter()));
        @fclose($this->rChunkHandle);
        $this->rChunkHandle = null;

        if (!$bOk || !@rename($this->sChunkTmp, $this->sChunkFinal)) {
            @unlink($this->sChunkTmp);
            bx_log('sys_cron_jobs', 'gf_sitemap ERROR: cannot write ' . $this->sChunkFinal . ' — check ' . $this->sCacheDir . ' permissions/disk space');
            $this->bStreamError = true;
            return false;
        }

        @chmod($this->sChunkFinal, 0666);
        return true;
    }

    /**
     * Finish the stream: guarantee at least one (possibly empty) chunk exists,
     * flush the last one, and return ['urls' => int, 'chunks' => int] — or
     * false if any write failed along the way.
     * @return array|false
     */
    protected function streamFinish()
    {
        // an empty site still needs a valid, empty sitemap.xml (chunk 1)
        if ($this->iChunkIndex === 0 && !$this->openNextChunk())
            return false;

        if (!$this->closeChunk())
            return false;

        if ($this->bStreamError)
            return false;

        return ['urls' => $this->iTotalUrls, 'chunks' => $this->iChunkIndex];
    }

    protected function renderIndex($iChunks)
    {
        $sLastMod = gmdate('Y-m-d\TH:i:s\Z');

        $s = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        for ($i = 1; $i <= $iChunks; $i++)
            $s .= "<sitemap><loc>" . htmlspecialchars(BX_DOL_URL_ROOT . 'sitemap-' . $i . '.xml', ENT_QUOTES | ENT_XML1) . "</loc><lastmod>" . $sLastMod . "</lastmod></sitemap>\n";

        return $s . '</sitemapindex>' . "\n";
    }

    /** Atomic write: temp file + rename, so readers never see a half-written sitemap. */
    protected function writeCacheFile($sFile, $sContent)
    {
        $sTmp = $sFile . '.tmp.' . getmypid();
        if (false === @file_put_contents($sTmp, $sContent) || !@rename($sTmp, $sFile)) {
            @unlink($sTmp);
            bx_log('sys_cron_jobs', 'gf_sitemap ERROR: cannot write ' . $sFile . ' — check ' . $this->sCacheDir . ' permissions/disk space');
            return false;
        }
        @chmod($sFile, 0666);
        return true;
    }
}
