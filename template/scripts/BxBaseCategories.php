<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * Multi Categories object representation.
 * @see BxDolCategories
 */
class BxBaseCategories extends BxDolCategories
{
    protected $_oTemplate;

    public function __construct($oTemplate = null)
    {
        parent::__construct();

        if($oTemplate)
            $this->_oTemplate = $oTemplate;
        else
            $this->_oTemplate = BxDolTemplate::getInstance();
    }

    public function getCategoriesList($bAsArray = false)
    {
        $aContextInfo = bx_get_page_info();

        $mProfileContextId = false;
        if($aContextInfo !== false)
            $mProfileContextId = $aContextInfo['context_profile_id'];

        if($mProfileContextId)
            $aCats = $this->getData([
                'type' => 'by_module&context_with_num', 
                'module' => $this->_sModule, 
                'context_id' => $mProfileContextId
            ]);
        else
            $aCats = $this->getData([
                'type' => 'by_module_with_num', 
                'module' => $this->_sModule
            ]);

        $aTmplVarsCats = [];
        foreach($aCats as $aCat) {
            $bIcon = !empty($aCat['icon']);

            $sValue = $aCat['value'];

            $aTmplVarsCats[] = array_merge([
                'url' => $this->getUrl($this->_sModule, $sValue, $mProfileContextId ? '&context_id=' . $mProfileContextId : ''),
                'name' => _t($sValue),
                'value' => $sValue,
                'num' => (int)$aCat['num'],
            ], !$this->_bIsApi ? [
                'bx_if:show_icon' => [
                    'condition' => $bIcon,
                    'content' => [
                        'bx_if:show_icon_font' => [
                            'condition' => $bIcon && $aCat['icon_type'] == 'icon',
                            'content' => [
                                'icon_name' => $aCat['icon'],
                            ]
                        ],
                        'bx_if:show_icon_html' => [
                            'condition' => $bIcon && in_array($aCat['icon_type'], ['emoji', 'image']),
                            'content' => [
                                'icon_code' => $aCat['icon'],
                            ]
                        ],
                    ]
                ],
                'selected_class' => $sValue == bx_get('category') ? 'bx-menu-tab-active' : '',
            ] : [
                'icon_type' => $aCat['icon_type'],
                'icon' => $aCat['icon']
            ]);
        }

        if($bAsArray)
            return $aTmplVarsCats;

        return !empty($aTmplVarsCats) ? $this->_oTemplate->parseHtmlByName('category_list_multi.html', [
            'bx_repeat:cats' => $aTmplVarsCats
        ]) : '';
    }
}

/** @} */
