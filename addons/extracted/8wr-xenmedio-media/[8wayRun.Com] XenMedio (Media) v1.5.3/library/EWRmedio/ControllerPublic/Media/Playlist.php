<?php

class EWRmedio_ControllerPublic_Media_Playlist extends XenForo_ControllerPublic_Abstract
{
	public $perms;
	public $slugs;

	public function actionIndex()
	{
		$playID = $this->_input->filterSingle('playlist_id', XenForo_Input::UINT);

		if (!$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($playID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/playlists'));
		}

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/playlist', $playlist));

		$viewParams = array(
			'perms' => $this->perms,
			'playlist' => $playlist,
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getMediaByPlaylist($playlist),
		);

		return $this->responseView('EWRmedio_ViewPublic_PlaylistView', 'EWRmedio_PlaylistView', $viewParams);
	}

	public function actionEdit()
	{
		if (!$this->perms['playlist']) { return $this->responseNoPermission(); }

		$playID = $this->_input->filterSingle('playlist_id', XenForo_Input::UINT);

		if (!$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($playID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'playlist_name' => XenForo_Input::STRING,
				'playlist_media' => XenForo_Input::ARRAY_SIMPLE,
				'submit' => XenForo_Input::STRING,
			));
			$input['playlist_id'] = $playlist['playlist_id'];
			$input['playlist_description'] = $this->getHelper('Editor')->getMessageText('playlist_description', $this->_input);

			$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->updatePlaylist($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/playlist', $playlist));
		}

		$viewParams = array(
			'playlist' => $playlist,
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getMediaByPlaylist($playlist),
		);

		return $this->responseView('EWRmedio_ViewPublic_PlaylistEdit', 'EWRmedio_PlaylistEdit', $viewParams);
	}

	public function actionDelete()
	{
		if (!$this->perms['playlist']) { return $this->responseNoPermission(); }

		$playID = $this->_input->filterSingle('playlist_id', XenForo_Input::UINT);

		if ($playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($playID))
		{
			if ($this->_request->isPost())
			{
				$this->getModelFromCache('EWRmedio_Model_Playlists')->deletePlaylist($playlist);
			}
			else
			{
				return $this->responseView('EWRmedio_ViewPublic_PlaylistDelete', 'EWRmedio_PlaylistDelete', array('playlist' => $playlist));
			}
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
	}

	public function actionAddto()
	{
		if (!$this->perms['playlist']) { return $this->responseNoPermission(); }

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'playlist_id' => XenForo_Input::UINT,
				'media_id' => XenForo_Input::UINT,
				'media_url' => XenForo_Input::STRING,
				'submit' => XenForo_Input::STRING,
			));

			if ($input['media_url'])
			{
				if (preg_match('#media/([^/]+)#i', $input['media_url'], $matches))
				{
					$input['media_id'] = explode('.', $matches[1]);
					$input['media_id'] = end($input['media_id']);
				}
			}

			$playID = $this->_input->filterSingle('playlist_id', XenForo_Input::UINT);

			if (!$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($playID))
			{
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/playlist/create/'.$input['media_id']));
			}

			$this->getModelFromCache('EWRmedio_Model_Playlists')->addToPlaylist($playlist, $input);

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/playlist', $playlist));
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media'));
	}

	public function actionCreate()
	{
		if (!$this->perms['playlist']) { return $this->responseNoPermission(); }

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'playlist_name' => XenForo_Input::STRING,
				'media_id' => XenForo_Input::UINT,
				'submit' => XenForo_Input::STRING,
			));
			$input['playlist_description'] = $this->getHelper('Editor')->getMessageText('playlist_description', $this->_input);

			$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->updatePlaylist($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/playlist', $playlist));
		}

		$viewParams = array(
			'mediaID' => !empty($this->slugs[2]) ? (int)$this->slugs[2] : 0,
		);

		return $this->responseView('EWRmedio_ViewPublic_PlaylistCreate', 'EWRmedio_PlaylistCreate', $viewParams);
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
		$playIDs = array();
		foreach ($activities AS $activity)
		{
			if (!empty($activity['params']['playlist_id']))
			{
				$playIDs[$activity['params']['playlist_id']] = $activity['params']['playlist_id'];
			}
		}

		$playlistData = array();
		if ($playIDs)
		{
			$playModel = XenForo_Model::create('EWRmedio_Model_Playlists');
			$playlists = $playModel->getPlaylistsByIDs($playIDs);

			foreach ($playlists AS $playlist)
			{
				$playlistData[$playlist['playlist_id']] = array(
					'title' => $playlist['playlist_name'],
					'url' => XenForo_Link::buildPublicLink('media/playlist', $playlist)
				);
			}
		}

        $output = array();
        foreach ($activities as $key => $activity)
		{
			$playlist = false;
			if (!empty($activity['params']['playlist_id']))
			{
				$playIDs = $activity['params']['playlist_id'];
				if (isset($playlistData[$playIDs]))
				{
					$playlist = $playlistData[$playIDs];
				}
			}

			if ($playlist)
			{
				$output[$key] = array(new XenForo_Phrase('viewing_media_playlist'), $playlist['title'], $playlist['url'], false);
			}
			else
			{
				$output[$key] = new XenForo_Phrase('viewing_media_library');
			}
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