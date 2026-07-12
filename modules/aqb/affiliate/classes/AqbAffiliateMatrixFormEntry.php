<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

/**
 * Create/Edit entry form
 */
class AqbAffiliateMatrixFormEntry extends BxBaseModTextFormEntry
{
    protected $_sModule;
    protected $_oModule;
    protected $_sJsObject;
    protected $_sPrefix = '';
    protected $_bOpened = false;
    private $_oConfig = null;
    private $_oDb = null;
    private $_oTemplate = null;

    function __construct($aCustomForm = array(), $bOpened = false)
    {
        $this->MODULE = 'aqb_affiliate';
        $this->_oModule = BxDolModule::getInstance($this -> MODULE);
        $this->_oConfig = &$this->_oModule->_oConfig;
        $this->_oDb = &$this->_oModule->_oDb;
        $this->_oTemplate = &$this->_oModule->_oTemplate;

        $this->_sJsObject = $this->_oModule->_oConfig->getJsObject('utils');
        $this->_sPrefix = '_' . $this->_oConfig->getName();
        $this->_bOpened = $bOpened;

        $oCNF = &$this->_oModule->_oConfig->CNF;

        if (empty($aCustomForm)) {
            $aCustomForm = array(
                'form_attrs' => array(
                    'method' => 'post',
                    'id' => $oCNF['OBJECT_GRID_MATRIX'],
                    'action' => BX_DOL_URL_ROOT . 'grid.php?' . bx_encode_url_params($_GET, array('ids', '_r'))
                ),
                'params' => array(
                    'db' => array(
                        'table' => $oCNF['PROGRAMS'],
                        'key' => $oCNF['FMP_ID'],
                        'submit_name' => "submit-matrix",
                    ),
                ),
                'inputs' => array(
                    'add_matrix' => array(
                        'type' => 'custom',
                        'content' => '<button class="bx-btn" type="button" onclick="' . $this->_sJsObject . '.getProgramForm()">
                                      <i class="sys-icon plus"></i><u>' . _t('_aqb_affiliate_add_matrix_button') . '</u></button>'
                    )
                )
            );

            $aCustomForm['inputs'] = array_merge($aCustomForm['inputs'], $this->_oTemplate->getStudioMatrixForm());
        }

        parent::__construct ($aCustomForm, $this->_oTemplate);

        $this -> addCssJsUi();
    }

    function genRowBlockHeader(&$aInput)
    {
        $aAttrs = empty($aInput['attrs']) ? '' : $aInput['attrs'];

        // if there is no caption - show divider only

        if (empty($aInput['caption'])) {
            $sCode = $this->{$this->_sSectionClose}();
            $sCode .= $this->{$this->_sSectionOpen}($aAttrs);
            return $sCode;
        }

        // if section is collapsed by default, add necessary code

        $sClassAddCollapsable = '';
        if (!$this->_bOpened && isset($aInput['collapsed']) and $aInput['collapsed'])
            $sClassAddCollapsable = ' bx-form-collapsed bx-form-section-hidden';

        // display section with caption

        $sCode = $this->{$this->_sSectionClose}();

        if (empty($aAttrs))
            $aAttrs = array('class' => 'bx-form-collapsable ' . $sClassAddCollapsable);
        else
            $aAttrs['class'] .= ' bx-form-collapsable ' . $sClassAddCollapsable;


        if (isset($this->aParams['view_mode']) && $this->aParams['view_mode'])
            $sLegend = '<legend class="bx-def-padding-left bx-def-padding-sec-right bx-def-font-grayed bx-def-font-h3">' . bx_process_output($aInput['caption']) . (!empty($aInput['info']) ? '<br /><span>' . $aInput['info'] . '</span>' : '') . '</legend>';
        else
            $sLegend = '<legend class="bx-def-padding-left bx-def-padding-sec-right bx-def-font-grayed bx-def-font-h3"><a href="javascript:void(0);">' . bx_process_output($aInput['caption']) . '</a>' . (!empty($aInput['info']) ? '<br /><span>' . $aInput['info'] . '</span>' : '') . '</legend>';

        $sCode .= $this->{$this->_sSectionOpen}($aAttrs, $sLegend);

        return $sCode;
    }
}

/** @} */
