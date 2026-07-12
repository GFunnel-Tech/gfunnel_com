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

bx_import('BxTemplGrid');

class AqbAutoonlineRulesGrid extends BxTemplGrid
{
    public function __construct ($aOptions, $oTemplate = false)
    {
        parent::__construct ($aOptions, $oTemplate);
    }

    protected function _getCellAdvSchedule ($mixedValue, $sKey, $aField, $aRow) {
        $sAttr = $this->_convertAttrs(
            $aField, 'attr_cell',
            false,
            isset($aField['width']) ? 'width:' . $aField['width'] : false // add default styles
        );

        if ($aRow['time'] == '00:00 - 23:59') $aRow['time'] = '';
        if ($aRow['weekdays'] == 127) $aRow['weekdays'] = '';

        if ($aRow['weekdays']) {
            bx_import('BxDolForm');
            bx_import('BxDolFormQuery');
            $aValues = BxDolFormCheckerHelper::displaySet($aRow['weekdays']);
            $aPreValues = BxDolFormQuery::getDataItems('Weekdays', true);
            $aValuesToDisplay = array();
            foreach ($aValues as $iValue) $aValuesToDisplay[] = $aPreValues[$iValue];
            $aRow['weekdays'] = implode(', ', $aValuesToDisplay);
        }

        return '<td ' . $sAttr . '>'.$aRow['weekdays'].'<br />'.$aRow['time'].'</td>';
    }

    protected function _getCellData($sKey, $aField, $aRow)
    {
        if (isset($aRow[$sKey])) {
            $value = $aRow[$sKey];

            switch ($sKey) {
                case 'accounts_count':
                    list($from, $to) = explode('-', $value);
                    if ($from == $to) $value = $from;
                break;
                case 'with_ava_only':
                    $value = $value == 1 ? _t('_Yes') : '&nbsp;';
                break;
                case 'online_time':
                    $h = floor($value / 60);
                    $m = $value % 60;
                    $value = sprintf('%02d', $h).':'.sprintf('%02d', $m);
                break;
            }

            return bx_process_output($value);
        } else {
            return _t('_undefined');
        }
    }

    public function performActionRun()
    {
        $iAffected = 0;
        $aIds = bx_get('ids');
        if (!$aIds || !is_array($aIds)) {
            echoJson(array());
            exit;
        }

        $aIdsAffected = array ();
        foreach ($aIds as $mixedId) {
            $iAffected += bx_srv('aqb_autoonline', 'exec_rule', array($mixedId));
            $aIdsAffected[] = $mixedId;
        }

        echoJson(array(
            //'msg' => $iAffected > 0 ? _t('_aqb_autoonline_run_succ', $iAffected) : _t('_aqb_autoonline_run_fail'),
            'msg' => _t('_aqb_autoonline_run_succ', $iAffected),
            'grid' => $this->getCode(false),
            'blink' => $aIdsAffected,
        ));
    }

    public function performActionAdd()
    {
        $oForm = BxDolForm::getObjectInstance('aqb_autoonline_form', 'aqb_autoonline_form_add');
        if (!$oForm) {
            echoJson(array('popup' => '"aqb_autoonline_form" form object or "aqb_autoonline_form_add" display is not defined'), true);
            exit;
        }
        $oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT.'grid.php?o=' . $this->_sObject . '&a=add';

        $oForm->initChecker();

        if ($oForm->isSubmittedAndValid()) {
            $iNewId = $oForm->insert();
            if ($iNewId)
                $aRes = array('grid' => $this->getCode(false), 'blink' => $iNewId);
            else
                $aRes = array('msg' => "Error occured"); // if record adding failed, display error message
            echoJson($aRes, true);
        } else {
            $s = '<div class="bx-def-padding bx-def-color-bg-block">' . $oForm->getCode() . '</div>
                <script>
                    function aqb_autoonline_ajax_form() {
                        $("#'.$oForm->aFormAttrs['id'].'").ajaxForm({
                            dataType: "json",
                            beforeSubmit: function (formData, jqForm, options) {
                                bx_loading($("#' . $oForm->aFormAttrs['id'] . '"), true);
                            },
                            success: function (data) {
                                $(".bx-popup-active:visible").dolPopupHide();
                                glGrids.' . $this->_sObject . '.processJson(data, "add");
                            }
                        });
                    }
                </script>';

            echoJson(array('popup' => array(
                'html' => $s,
                'options' => array(
                    'onShow' => 'aqb_autoonline_ajax_form(); aqb_autoonline_init_custom_range();')
                )), true);
        }
    }
}

/** @} */
