<?php

class EWRmedio_ControllerPublic_Account extends XFCP_EWRmedio_ControllerPublic_Account
{
	public function actionPreferences()
	{
		$response = parent::actionPreferences();

		$response->subView->params['media']['media_watch_state'] = $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getDefaultWatchByUserId(XenForo_Visitor::getUserId());

		return $response;
	}
	
	public function actionPreferencesSave()
	{
		$response = parent::actionPreferencesSave();
		
		if ($this->_input->filterSingle('media_watch_state', XenForo_Input::UINT))
		{
			$settings['media_watch_state'] = ($this->_input->filterSingle('media_watch_state_email', XenForo_Input::UINT)
				? 'watch_email'
				: 'watch_no_email'
			);
		}
		else
		{
			$settings['media_watch_state'] = '';
		}
		
		$this->getModelFromCache('EWRmedio_Model_MediaWatch')->setDefaultWatchByUserId(XenForo_Visitor::getUserId(), $settings['media_watch_state']);
		
		return $response;
	}
}