<?php

class EWRmedio_ControllerPublic_Watched extends XFCP_EWRmedio_ControllerPublic_Watched
{
	protected function _preDispatch($action)
	{
		$this->_assertRegistrationRequired();
	}
	
	public function actionMedia()
	{
		$visitor = XenForo_Visitor::getInstance();

		$viewParams = array(
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getMediaWatchedByUser(
				$visitor['user_id'],
				1,
				XenForo_Application::get('options')->discussionsPerPage,
				true
			),
			'viewLast' => true,
		);

		return $this->responseView('EWRmedio_ViewPublic_Watch', 'EWRmedio_Watch', $viewParams);
	}
	
	public function actionMediaAll()
	{
		$visitor = XenForo_Visitor::getInstance();

		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = XenForo_Application::get('options')->discussionsPerPage;
		$count = $this->getModelFromCache('EWRmedio_Model_MediaWatch')->countMediaWatchedByUser($visitor['user_id']);

		$this->canonicalizePageNumber($start, $stop, $count, 'watched/media/all');

		$viewParams = array(
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getMediaWatchedByUser(
				$visitor['user_id'],
				$start,
				$stop,
				false
			),
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'viewLast' => true,
			'subscribeOptions' => true,
		);

		return $this->responseView('EWRmedio_ViewPublic_WatchAll', 'EWRmedio_WatchAll', $viewParams);
	}
	
	public function actionMediaUpdate()
	{
		$this->_assertPostOnly();

		$input = $this->_input->filter(array(
			'media_ids' => array(XenForo_Input::UINT, 'array' => true),
			'do' => XenForo_Input::STRING
		));

		$watch = $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getUserMediaWatchByMediaIds(XenForo_Visitor::getUserId(), $input['media_ids']);

		foreach ($watch AS $mediaWatch)
		{
			$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_MediaWatch');
			$dw->setExistingData($mediaWatch, true);

			switch ($input['do'])
			{
				case 'stop':
					$dw->delete();
					break;

				case 'email':
					$dw->set('email_subscribe', 1);
					$dw->save();
					break;

				case 'no_email':
					$dw->set('email_subscribe', 0);
					$dw->save();
					break;
			}
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$this->getDynamicRedirect(XenForo_Link::buildPublicLink('watched/media/all'))
		);
	}
	
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('managing_account_details');
	}
}