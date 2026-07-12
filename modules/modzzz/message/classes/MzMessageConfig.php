<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) Modzzz
 * 
 * @defgroup    Profile Message Profile Message 
 * @ingroup     ModzzzModules
 *
 * @{
 */
  
class MzMessageConfig extends BxBaseModTextConfig 
{
	protected $sIconsUrl;
    protected $_aHtmlIds;

	function __construct($aModule) 
    {
	    parent::__construct($aModule); 
 
        $this->CNF = array (
		  
            // database tables
            'TABLE_ENTRIES' => $aModule['db_prefix'] . '_data',
            'TABLE_ENTRIES_FULLTEXT' => 'title_text',

			'URI_MANAGE_ENTRY' => 'manage-profile-message',

			'OBJECT_GRID' => 'mz_message_manage', 
            'OBJECT_PRIVACY_VIEW' => 'mz_message_allow_view_to',

            // database fields
            'FIELD_ID' => 'id',
            'FIELD_AUTHOR' => 'author',
            'FIELD_ADDED' => 'added',
            'FIELD_CHANGED' => 'changed',
            'FIELD_EXPIRE' => 'expire',
            'FIELD_PUBLISHED' => 'published',
            'FIELD_TITLE' => 'title',
            'FIELD_TEXT' => 'text',
            'FIELD_TEXT_ID' => 'post-text',
            'FIELD_CATEGORY' => 'cat',
            'FIELD_MULTICAT' => 'multicat', 
            'FIELD_STATUS' => 'status',
            'FIELD_STATUS_ADMIN' => 'status_admin', 
            'FIELD_LABELS' => 'labels', 
            'FIELD_ALLOW_VIEW_TO' => 'allow_view_to',

			'OBJECT_FORM_ENTRY' => 'mz_message',
            'OBJECT_FORM_ENTRY_DISPLAY_ADD' => 'mz_message_entry_add',
            'OBJECT_FORM_ENTRY_DISPLAY_EDIT' => 'mz_message_entry_edit',
            'OBJECT_FORM_ENTRY_DISPLAY_DELETE' => 'mz_message_entry_delete', 
		);   
 
        $this->_aJsClasses = array_merge($this->_aJsClasses, array(
			'manage_tools' => 'MzMessageManageTools',
            'studio' => 'MzMessageStudio',
        ));

        $this->_aJsObjects = array_merge($this->_aJsObjects, array(
            'manage_tools' => 'oMzMessageManageTools',
            'studio' => 'oMzMessageStudio',
        ));

		$this->_aGridObjects = array(
            'message_manage' => $this->CNF['OBJECT_GRID'],
        ); 
    } 
  
    public function getHtmlIds($sKey = '')
    {
        if(empty($sKey))
            return $this->_aHtmlIds;

        return isset($this->_aHtmlIds[$sKey]) ? $this->_aHtmlIds[$sKey] : '';
    }

}

/** @} */
