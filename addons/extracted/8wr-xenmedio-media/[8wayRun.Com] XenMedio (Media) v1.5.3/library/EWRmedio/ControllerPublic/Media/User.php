<?php

class EWRmedio_ControllerPublic_Media_User extends XenForo_ControllerPublic_Abstract
{
	public $perms;

	public function actionIndex()
	{
		$userID = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_mediacount;
		$count = $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaCount('user', $user['user_id']);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/user', $user, array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media/user', $user);

		$viewParams = array(
			'perms' => $this->perms,
			'user' => $user,
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList($start, $stop, 'date', 'DESC', 'user', $user['user_id']),
		);

		if ($this->_noRedirect())
		{
			return $this->responseView('EWRmedio_ViewPublic_UserView', 'EWRmedio_UserView_Simple', $viewParams);
		}
		else
		{
			$viewParams['sidebar'] = $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar();
			return $this->responseView('EWRmedio_ViewPublic_UserView', 'EWRmedio_UserView', $viewParams);
		}
	}
	
	public function actionPlaylists()
	{
		$userID = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}
		
		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_mediacount;
		$count = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistsByUserCount($user);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/user/playlists', $user, array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media/user/playlists', $user);
		
		$viewParams = array(
			'perms' => $this->perms,
			'user' => $user,
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'playlists' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistsByUser($start, $stop, $user),
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_UserPlaylists', 'EWRmedio_UserPlaylists', $viewParams);
	}

	public function actionRss()
	{
		$userID = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia(null, 'user', $user['user_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
        $output = array();
        foreach ($activities as $key => $activity)
		{
			$output[$key] = new XenForo_Phrase('viewing_media_library');
        }

        return $output;
	}

	protected function _preDispatch($action)
	{
		parent::_preDispatch($action);

		$this->perms = $this->getModelFromCache('EWRmedio_Model_Perms')->getPermissions();
		$this->slugs = explode('/', $this->_routeMatch->getMinorSection());

		if (!$this->perms['browse']) { throw $this->getNoPermissionResponseException(); }
	}
}