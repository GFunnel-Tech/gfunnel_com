<?php defined('BX_DOL') or defined('BX_DOL_INSTALL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolAlertsStats extends BxDol
{
    private static array $logs = [];
    private static array $stats = [];
    private static bool $registered = false;

    public static function log(object $oAlert): void
    {
        if (!self::$registered) {
            self::$registered = true;
            register_shutdown_function([self::class, 'flush']);
        }

        $s = $oAlert->sUnit . ':' . $oAlert->sAction;
        if (!isset(self::$logs[$s])) {
            self::$logs[$s] = [
                'unit' => $oAlert->sUnit,
                'action' => $oAlert->sAction,
                'object' => $oAlert->iObject,
                'sender' => $oAlert->iSender,
                'extra' => $oAlert->aExtras,
                'counter' => 1,
            ];
        }
        else {
            self::$logs[$s]['counter']++;
        }
    }

    public static function hit(string $hookName, float $timing): void
    {
        if (!self::$registered) {
            self::$registered = true;
            register_shutdown_function([self::class, 'flush']);
        }

        if (!isset(self::$stats[$hookName])) {
            self::$stats[$hookName] = [
                'count' => 0,
                'last' => time(),
                'timing' => $timing,
            ];
        }

        self::$stats[$hookName]['count']++;
        self::$stats[$hookName]['last'] = time();
        self::$stats[$hookName]['timing'] += $timing;
    }

    public static function flush(): void
    {
        self::flushStats();
        self::flushLogs();
    }

    public static function flushStats(): void
    {
        if (!self::$stats || !getParam('sys_alerts_stats'))
            return;

        foreach (self::$stats as $hookName => $data) {

            $sql = "
                UPDATE `sys_alerts_handlers`
                SET
                    `ts` = :last,
                    `timing` = :timing,
                    `call_count` = call_count + :count, 
                    `calls_per_request` = GREATEST(`calls_per_request`, :count)
                WHERE `name` = :name
            ";

            BxDolDb::getInstance()->query($sql, [
                'count' => $data['count'],
                'last' => $data['last'],
                'timing' => $data['timing'],
                'name' => $hookName,
            ]);
        }
    }

    public static function flushLogs(): void
    {
        if (!self::$logs)
            return;

        $oDb = BxDolDb::getInstance();
        if (!getParam('sys_alerts_stats'))
            $aMap = $oDb->getPairs('SELECT CONCAT(unit, ":", action) AS `key`, 1 AS `val` FROM sys_alerts_log', 'key', 'val');

        foreach (self::$logs as $s => $data) {
            $aExtraRefs = [];
            foreach($data['extra'] as $sKey => $mixedValue) {
                if (preg_match('/^(ref|override|return)_/', $sKey) || preg_match('/_(ref|override|return)$/', $sKey) || $sKey == 'result' || $sKey == 'res' || $sKey == 'return' || $sKey == 'ret' || $sKey == 'check_result') {
                    self::$logs[$s]['extra_refs'][] = $sKey;
                }
            }
            self::$logs[$s]['extra_refs'] = json_encode(self::$logs[$s]['extra_refs'] ?? []);
            self::$logs[$s]['extra'] = json_encode(self::$logs[$s]['extra']);            

            if (getParam('sys_alerts_stats')) {
                $oDb->query("
                    INSERT INTO sys_alerts_log
                    SET
                        unit = :unit,
                        action = :action,
                        object = :object,
                        sender = :sender,
                        extra = :extra,
                        extra_refs = :extra_refs,
                        ts = UNIX_TIMESTAMP(),
                        counter_total = :counter,
                        counter_24h = :counter,
                        counter_per_request = :counter
                    ON DUPLICATE KEY UPDATE
                        ts = UNIX_TIMESTAMP(),
                        counter_total = counter_total + :counter,
                        counter_24h = counter_24h + :counter,
                        counter_per_request = GREATEST(counter_per_request, :counter)
                ", self::$logs[$s]);
            } else {                
                $sKey = self::$logs[$s]['unit'] . ':' . self::$logs[$s]['action'];
                if (!isset($aMap[$sKey])) {
                    $oDb->query("
                        INSERT IGNORE INTO sys_alerts_log
                        SET
                            unit = :unit,
                            action = :action,
                            object = :object,
                            sender = :sender,
                            extra = :extra,
                            extra_refs = :extra_refs,
                            ts = 0,
                            counter_per_request = :counter
                    ", self::$logs[$s]);
                }
            }
        }
    }
}

/** @} */
