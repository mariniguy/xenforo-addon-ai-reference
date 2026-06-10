<?php

class EWRmedio_ControllerPublic_Thread extends XFCP_EWRmedio_ControllerPublic_Thread
{
	public function actionIndex()
	{
		$response = parent::actionIndex();
		$options = XenForo_Application::get('options');

		if ($response instanceof XenForo_ControllerResponse_View
			&& $options->EWRmedio_autolock
			&& in_array($response->params['forum']['node_id'], $options->EWRmedio_autoforum)
			&& $media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByThread($response->params['thread']['thread_id']))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
		}

		return $response;
	}
}