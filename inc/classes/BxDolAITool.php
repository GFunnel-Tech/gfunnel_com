<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

use NeuronAI\Tools\Tool;

class BxDolAITool extends Tool
{
    function getContentModules() 
    {
        $a = bx_srv('system', 'modules_list', [true, true], 'TemplServiceContent');
        return implode(', ', $a);
    }

    function convertArrayToKeyValue($data) {
        $result = [];
        
        foreach ($data as $item) {
            if (isset($item['name']) && isset($item['value'])) {
                $result[$item['name']] = $item['value'];
            }
        }
        
        return $result;
    }
}
