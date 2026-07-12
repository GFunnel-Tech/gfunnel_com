<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2018 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Autoonline Autoonline
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbAutoonlineStudioPage extends BxTemplStudioModule
{
    protected $oModule;

    function __construct($sModule, $mixedPageName, $sPage = "")
    {
        parent::__construct($sModule, $mixedPageName, $sPage);

        $this->oModule = BxDolModule::getInstance('aqb_autoonline');

        $this->aMenuItems = array(
            'rules' => array('name' => 'rules', 'icon' => 'cogs', 'title' => '_aqb_autoonline_rules'),
            'help' => array('name' => 'help', 'icon' => 'question', 'title' => '_aqb_autoonline_help'),
        );
    }

    protected function getSettings()
    {
        return $this->getRules();
    }

    protected function getRules()
    {
        return
            $this->getOnlineNowBlock().
            $this->getExistingRulesBlock();
    }

    protected function getOnlineNowBlock()
    {
        $iTotal = $this->oModule->_oDb->getAccountsTotal();
        $iOnline = $this->oModule->_oDb->getAccountsOnline();

        $aForm = array(
            'params' => array (
                'remove_form' => true,
                'view_mode' => true,
            ),
            'inputs' => array(
                'total' => array(
                    'type' => 'value',
                    'caption' => _t('_aqb_autoonline_total'),
                    'value' => $iTotal,
                ),
                'online_now' => array(
                    'type' => 'value',
                    'caption' => _t('_aqb_autoonline_online_now'),
                    'value' => $iOnline.' ('.ceil($iOnline/$iTotal*100).'%)',
                ),
            ),
        );

        $oForm = new BxTemplFormView($aForm);

        return DesignBoxContent(_t('_aqb_autoonline_online_now'), $oForm->getCode());
    }

    protected function getExistingRulesBlock()
    {
        bx_import('BxTemplGrid');
        $oGrid = BxDolGrid::getObjectInstance('aqb_autoonline_rules', BxDolStudioTemplate::getInstance());
        if (!$oGrid) die('"aqb_autoonline_rules" grid object is not defined');

        return DesignBoxContent(_t('_aqb_autoonline_rules'), $oGrid->getCode());
    }

    protected function getHelp ()
    {
        return _t('_aqb_autoonline_help_text');
    }

    protected function getPageCaptionHelp()
    {
        $oTemplate = BxDolStudioTemplate::getInstance();
        return $oTemplate->parseHtmlByName('page_caption_help.html', array('content' => _t('_aqb_autoonline_help_text')));
        // $sContent = '<a href="' . BX_DOL_URL_STUDIO . 'module.php?name=aqb_autoonline&page=help">' . _t('_aqb_autoonline_help') . "</a>";
        // return $oTemplate->parseHtmlByName('page_caption_help.html', array('content' => $sContent));
    }
}

/** @} */
