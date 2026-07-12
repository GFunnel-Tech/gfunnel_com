<?php defined('BX_DOL') or die('hack attempt');

bx_import('BxTemplGrid');
class BxPeopleGridCommon extends BxTemplGrid
{
    protected $MODULE; // Explicitly declare the property
    
    public function __construct ($aOptions, $oTemplate = false)
    {
    	$this->MODULE = 'bx_people';
        parent::__construct ($aOptions, $oTemplate);
		$iUserId = bx_get_logged_profile_id();
		$this->addMarkers(array(
	        	'author' => $iUserId
	        ));
    }
	
	
	protected function _getActionEdit ($sType, $sKey, $a, $isSmall = false) {
	//echo '<pre>';print_r($a);die;
    $sAttr = $this->_convertAttrs(
        $a, 'attr',
        'bx-btn bx-def-margin-sec-left' . ($isSmall ? ' bx-btn-small' : '') // add default classes
    );
	$href = 'edit-app/'.$a['attr']['bx_grid_action_data'];
    return '<button class="bx-btn bx-def-margin-thd" ><a href="'.$href.'"class="sys-icon pencil-alt"></a></button>';
	}
	
	protected function _getActionAdd ($sType, $sKey, $a, $isSmall = false) {
		
    $sAttr = $this->_convertAttrs(
        $a, 'attr',
        'bx-btn bx-def-margin-sec-left' . ($isSmall ? ' bx-btn-small' : '') // add default classes
    );
	
	return'<button class="bx-btn bx-def-margin-thd"><a href="add-app">Add App</a></button>';
    
	}
}

/** @} */
