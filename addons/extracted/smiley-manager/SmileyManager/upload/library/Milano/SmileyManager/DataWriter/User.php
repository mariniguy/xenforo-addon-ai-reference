<?php

class Milano_SmileyManager_DataWriter_User extends XFCP_Milano_SmileyManager_DataWriter_User
{
	protected function _getFields() 
	{
		$fields = parent::_getFields();
		
		$fields['xf_user_option']['quickload_smiley'] = array(
			'type' => self::TYPE_BOOLEAN,
			'default' => 0
		);
		
		return $fields;
	}

	protected function _preSave() 
	{		
		if (isset($GLOBALS['Milano_SmileyManager_ControllerPublic_Account'])) 
		{
			$GLOBALS['Milano_SmileyManager_ControllerPublic_Account']->SmileyManager_actionPreferencesSave($this);
		}

		return parent::_preSave();
	}
}