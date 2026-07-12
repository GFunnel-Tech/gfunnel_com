<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2021 AQB Soft
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
 * @defgroup    Advanced Menu Advanced Menu
 * @ingroup     UnaModules
 *
 * @{
 */

class AqbAdvancedMenuGridItems extends BxTemplGrid
{
    public function __construct ($aOptions, $oTemplate = false)
    {
        $this->MODULE = 'aqb_advanced_menu';
    	$this->_oModule = BxDolModule::getInstance($this->MODULE);
    	if(!$oTemplate)
			$oTemplate = $this->_oModule->_oTemplate;

        parent::__construct ($aOptions, $oTemplate);
    }

    private function getAddEditForm($bEdit = false) {
        $aEntry = false;
        $iEntry = 0;
        if ($bEdit) {
            $iEntry = !is_null($_REQUEST) && isset($_REQUEST['id']) ? intval($_REQUEST['id']) : intval($_REQUEST['ids'][0]);
            $aEntry = $this->_oModule->_oDb->getEntryDetails($iEntry);
        }

        $oForm = $this->getAddEditFormObject($iEntry);

        $oForm->aFormAttrs['action'] = BX_DOL_URL_ROOT.'grid.php?o=' . $this->_sObject . '&a='.($aEntry ? 'edit' : 'add');

        if ($aEntry) {
            $aEntry['menu_item_eval'] = htmlspecialchars($aEntry['menu_item_eval']);
            $aEntry['menu_item_name'] = htmlspecialchars($aEntry['menu_item_name']);
            $aEntry['name'] = htmlspecialchars($aEntry['name']);
        }
        $oForm->initChecker($aEntry);

        if ($oForm->isSubmittedAndValid()) {
            if ($bEdit) {
                $this->_oModule->_oDb->updateEntry($aEntry['id'], $_POST);
                $iResult = $aEntry['id'];
            } else {
                $iResult = $this->_oModule->_oDb->insertEntry($_POST);
            }

            if ($iResult)
                $aRes = ['grid' => $this->getCode(false), 'blink' => $iResult];
            else
                $aRes = ['msg' => "Error occured"]; // if record adding failed, display error message
            echoJson($aRes);
        } else {
            $s = '<div class="bx-def-padding bx-def-color-bg-block">' . $oForm->getCode(true) . '</div>
                <style>
                    #aqb_advanced_menu_form .bx-form-value .bx-form-input-wrapper-textarea .CodeMirror {
                        height: 15rem;
                    }    
                </style>
                <script>
                    function aqb_advanced_menu_ajax_form() {                        
                        CodeMirror.fromTextArea($(\'#aqb_menu_item_eval\').get(0), {
                            lineNumbers: true,
                            mode: "php",
                            htmlMode: true,
                            matchBrackets: true
                        });
                        
                        $("#'.$oForm->aFormAttrs['id'].'").ajaxForm({
                            dataType: "json",
                            beforeSubmit: function (formData, jqForm, options) {
                                bx_loading($("#' . $oForm->aFormAttrs['id'] . '"), true);
                            },
                            success: function (data) {
                                $(".bx-popup-active:visible").dolPopupHide();
                                glGrids.' . $this->_sObject . '.processJson(data, "'.($bEdit ? 'edit' : 'add').'");
                            }
                        });
                    }
                </script>';

            echoJson(['popup' => [
                'html' => $s,
                'options' => [
                    'onBeforeShow' => $bEdit ? "$('#grid-popup-aqb_advanced_menu-add').remove();" : "$('#grid-popup-aqb_advanced_menu-edit').remove();",
                    'onShow' => 'aqb_advanced_menu_ajax_form();',
                    'closeOnOuterClick' => false,
                ]]]
            );
        }
    }
    public function performActionAdd()
    {
        return $this->getAddEditForm();
    }

    public function performActionEdit()
    {
        return $this->getAddEditForm(true);
    }

    public function performActionDelete() {
        $aIds = bx_get('ids');
        if (!$aIds || !is_array($aIds)) {
            echoJson(array());
            exit;
        }

        foreach ($aIds as $iEntry) {
            $this->_oModule->_oDb->deleteEntry($iEntry);
        }

        echoJson(array(
            'grid' => $this->getCode(false),
        ));
    }

    private function getAddEditFormObject($iEntry) {
        $aForm = [
            'form_attrs' => [
                'id' => 'aqb_advanced_menu_form',
                'method' => 'post',
                'action' => '',
            ],
            'params' => [
                'db' => [
                    'submit_name' => 'submit',
                ],
            ],
            'inputs' => [
                'id' => [
                    'type' => 'hidden',
                    'name' => 'id',
                    'value' => $iEntry,
                ],
                'submit_hidden' => [
                    'type' => 'hidden',
                    'name' => 'submit',
                    'value' => 1,
                ],
                'name' => [
                    'type' => 'text',
                    'name' => 'name',
                    'required' => true,
                    'caption' => _t('_aqb_advanced_menu_form_field_name'),
                    'info' => _t('_aqb_advanced_menu_form_field_name_info'),
                    'checker' => [
                        'func' => 'Avail',
                        'error' => _t('_aqb_advanced_menu_required_field'),
                    ],
                ],
                'menu_item_name' => [
                    'type' => 'text',
                    'name' => 'menu_item_name',
                    'required' => true,
                    'caption' => _t('_aqb_advanced_menu_form_field_menu_item_name'),
                    'info' => _t('_aqb_advanced_menu_form_field_menu_item_name_info'),
                    'checker' => [
                        'func' => 'Avail',
                        'error' => _t('_aqb_advanced_menu_required_field'),
                    ],
                ],
                'menu_item_eval' => [
                    'type' => 'textarea',
                    'name' => 'menu_item_eval',
                    'caption' => _t('_aqb_advanced_menu_form_field_menu_item_eval'),
                    'info' => _t('_aqb_advanced_menu_form_field_menu_item_eval_info'),
                    'required' => true,
                    'checker' => [
                        'func' => 'Avail',
                        'error' => _t('_aqb_advanced_menu_required_field'),
                    ],
                    'attrs' => [
                        'id' => 'aqb_menu_item_eval',
                    ],
                ],
                'menu_item_eval_desc' => [
                    'type' => 'custom',
                    'caption' => _t('_aqb_advanced_menu_form_field_help'),
                    'content' => _t('_aqb_advanced_menu_form_field_help_cont'),
                ],
                'actions' => array(
                    'type' => 'input_set',
                    0 => array(
                        'type' => 'submit',
                        'name' => 'submit',
                        'value' => _t('_Submit'),
                    ),
                    1 => array(
                        'type' => 'button',
                        'name' => 'cancel',
                        'value' => _t('_Cancel'),
                        'attrs' => array(
                            'style' => 'margin-left: 20px;',
                            'onclick' => '$(".bx-popup-active:visible").dolPopupHide();',
                        ),
                    ),
                ),
            ],
        ];
        return new BxTemplFormView($aForm);
    }
}

/** @} */
