<?php

class Milano_SmileyManager_ControllerPublic_Account extends XFCP_Milano_SmileyManager_ControllerPublic_Account
{	
	public function actionPreferencesSave() 
	{
		$GLOBALS['Milano_SmileyManager_ControllerPublic_Account'] = $this;
		
		return parent::actionPreferencesSave();
	}
	
	public function SmileyManager_actionPreferencesSave(XenForo_DataWriter_User $dw)
	{
		$quickloadSmiley = $this->_input->filterSingle('quickload_smiley', XenForo_Input::UINT);

		$dw->set('quickload_smiley', $quickloadSmiley);
	}
}