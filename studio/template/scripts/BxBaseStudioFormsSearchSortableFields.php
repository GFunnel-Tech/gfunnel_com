<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaView UNA Studio Representation classes
 * @ingroup     UnaStudio
 * @{
 */

class BxBaseStudioFormsSearchSortableFields extends BxBaseStudioFormsSearchFormFields
{
    public function __construct($aOptions, $oTemplate = false)
    {
        parent::__construct($aOptions, $oTemplate);

        $this->sUrlPage .= 'search_sortable_fields';
    }

    public function performActionReset()
    {
        $mixedResult = BxDolSearchExtended::getObjectInstance($this->sForm)->resetSortableFields();
        if($mixedResult === false)
            return echoJson(array('msg' => _t('_adm_from_err_search_forms_sortable_fields_reset')));

        echoJson(array('grid' => $this->getCode(false)));
    }

    protected function _addJsCss()
    {
        parent::_addJsCss();

        $this->_oTemplate->addJs(['forms_sortable_fields.js']);
    }
}

/** @} */
