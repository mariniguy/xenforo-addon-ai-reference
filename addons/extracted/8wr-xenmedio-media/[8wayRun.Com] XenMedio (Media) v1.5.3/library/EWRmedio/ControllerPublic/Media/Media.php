<?php

class EWRmedio_ControllerPublic_Media_Media extends XenForo_ControllerPublic_Abstract
{
	public $perms;
	public $slugs;

	public function actionIndex()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$pid = '';
		if (!empty($this->slugs[1]))
		{
			if ($playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($this->slugs[1]))
			{
				$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistWithMedia($playlist, $media);
				$pid = '/'.$playlist['playlist_id'];
			}
		}

		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_commentcount;
		$count = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentCount($media);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media'.$pid, $media, array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media'.$pid, $media);

		$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($media['category_id']);

		$viewParams = array(
			'perms' => $this->perms,
			'start' => $start,
			'stop' => $stop,
			'playlist' => !empty($playlist) ? $playlist : false,
			'media' => $this->getModelFromCache('EWRmedio_Model_Media')->updateViews($media),
			'customs' => $this->getModelFromCache('EWRmedio_Model_Custom')->getCustomValues($media),
			'keywords' => $this->getModelFromCache('EWRmedio_Model_Media')->getKeywordLinks($media),
			'playlistList' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByUserID(),
			'count' => $count,
			'comments' => $this->getModelFromCache('EWRmedio_Model_Comments')->getComments($media, $start, $stop),
			'breadCrumbs' => array_reverse($this->getModelFromCache('EWRmedio_Model_Lists')->getCrumbs($category)),
		);

		return $this->responseView('EWRmedio_ViewPublic_MediaView', 'EWRmedio_MediaView', $viewParams);
	}

	public function actionRss()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia($media),
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public function actionPopout()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$viewParams = array(
			'media' => $this->getModelFromCache('EWRmedio_Model_Media')->updateViews($media),
		);

		return $this->responseView('EWRmedio_ViewPublic_MediaPopout', 'EWRmedio_MediaPopout', $viewParams);
	}

	public function actionEdit()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if (!$this->perms['mod'] && $media['user_id'] !== XenForo_Visitor::getUserId()) { return $this->responseNoPermission(); }
		if ($this->perms['admin'] || $media['user_id'] == XenForo_Visitor::getUserId()) { $this->perms['alter'] = true; }

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'category_id' => XenForo_Input::UINT,
				'media_title' => XenForo_Input::STRING,
				'media_hours' => XenForo_Input::UINT,
				'media_minutes' => XenForo_Input::UINT,
				'media_seconds' => XenForo_Input::UINT,
				'media_keywords' => XenForo_Input::STRING,
				'media_keyarray' => XenForo_Input::ARRAY_SIMPLE,
				'media_keylinks' => XenForo_Input::ARRAY_SIMPLE,
				'media_oldlinks' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom1' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom2' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom3' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom4' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom5' => XenForo_Input::ARRAY_SIMPLE,
				'submit' => XenForo_Input::STRING,
			));
			$input['media_id'] = $media['media_id'];
			$input['media_description'] = $this->getHelper('Editor')->getMessageText('media_description', $this->_input);
			$input['bypass'] =  $this->perms['bypass'];

			if (!empty($input['media_keyarray']))
			{
				$input['media_keywords'] = implode(',', $input['media_keyarray']);
			}

			if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
			{
				return $this->responseCaptchaFailed();
			}

			$media = $this->getModelFromCache('EWRmedio_Model_Media')->updateMedia($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
		}

		$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($media['category_id']);

		$viewParams = array(
			'perms' => $this->perms,
			'captcha' => XenForo_Captcha_Abstract::createDefault(),
			'media' => $media,
			'customs' => $this->getModelFromCache('EWRmedio_Model_Custom')->getCustomOptions($media),
			'keylinks' => $this->getModelFromCache('EWRmedio_Model_Media')->getKeywordLinks($media),
			'fullList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList(),
			'services' => $this->getModelFromCache('EWRmedio_Model_Services')->getServices(),
			'breadCrumbs' => array_reverse($this->getModelFromCache('EWRmedio_Model_Lists')->getCrumbs($category)),
		);
		
		if (!(XenForo_Application::get('options')->EWRmedio_newkeywords))
		{
			$viewParams['keywords'] = $this->getModelFromCache('EWRmedio_Model_Media')->getKeywordNolinks($media);
		}

		return $this->responseView('EWRmedio_ViewPublic_MediaEdit', 'EWRmedio_MediaEdit', $viewParams);
	}

	public function actionDelete()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if ($media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			if (!$this->perms['mod'] && $media['user_id'] !== XenForo_Visitor::getUserId()) { return $this->responseNoPermission(); }

			if ($this->_request->isPost())
			{
				$this->getModelFromCache('EWRmedio_Model_Media')->deleteMedia($media);
			}
			else
			{
				return $this->responseView('EWRmedio_ViewPublic_MediaDelete', 'EWRmedio_MediaDelete', array('media' => $media));
			}
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
	}

	public function actionAlter()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		$this->_assertPostOnly();

		if ($media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			if (!$this->perms['admin'] && $media['user_id'] !== XenForo_Visitor::getUserId()) { return $this->responseNoPermission(); }

			$input = $this->_input->filter(array(
				'service_id' => XenForo_Input::UINT,
				'service_value' => XenForo_Input::STRING,
				'service_value2' => XenForo_Input::STRING,
				'submit' => XenForo_Input::STRING,
			));
			$input['media_id'] = $media['media_id'];
			if ($this->perms['admin']) { $input['bypass'] = true; }

			$this->getModelFromCache('EWRmedio_Model_Media')->alterMedia($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
	}

	public function actionThumb()
	{
		$this->_assertPostOnly();

		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if (!$this->perms['mod'] && $media['user_id'] !== XenForo_Visitor::getUserId()) { return $this->responseNoPermission(); }

		$fileTransfer = new Zend_File_Transfer_Adapter_Http();

		if ($fileTransfer->isUploaded('upload_file'))
		{
			$fileInfo = $fileTransfer->getFileInfo('upload_file');
			$fileName = $fileInfo['upload_file']['tmp_name'];

			$this->getModelFromCache('EWRmedio_Model_Thumbs')->buildThumb($media['media_id'], $fileName);
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/edit', $media));
	}

	public function actionLikes()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$likes = $this->getModelFromCache('XenForo_Model_Like')->getContentLikes('media', $mediaID);
		if (!$likes)
		{
			return $this->responseError(new XenForo_Phrase('no_one_has_liked_this_post_yet'));
		}

		$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($media['category_id']);

		$viewParams = array(
			'media' => $media,
			'breadCrumbs' => array_reverse($this->getModelFromCache('EWRmedio_Model_Lists')->getCrumbs($category)),
			'likes' => $likes
		);

		return $this->responseView('EWRmedio_ViewPublic_MediaLikes', 'EWRmedio_MediaLikes', $viewParams);
	}

	public function actionLike()
	{
		if (!$this->perms['like']) { return $this->responseNoPermission(); }

		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$viewingID = XenForo_Visitor::getUserId();

		if ($media['user_id'] == $viewingID)
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
		}

		$existingLike = $this->getModelFromCache('XenForo_Model_Like')->getContentLikeByLikeUser('media', $media['media_id'], $viewingID);

		if ($this->_request->isPost())
		{
			if ($existingLike)
			{
				$latestUsers = $this->getModelFromCache('XenForo_Model_Like')->unlikeContent($existingLike);
			}
			else
			{
				$latestUsers = $this->getModelFromCache('XenForo_Model_Like')->likeContent('media', $media['media_id'], $media['user_id']);
			}

			$liked = ($existingLike ? false : true);

			if ($this->_noRedirect() && $latestUsers !== false)
			{
				$media['likeUsers'] = $latestUsers;
				$media['likes'] += ($liked ? 1 : -1);
				$media['like_date'] = ($liked ? XenForo_Application::$time : 0);

				$viewParams = array(
					'media' => $media,
					'liked' => $liked,
				);

				return $this->responseView('EWRmedio_ViewPublic_MediaLikeConfirmed', '', $viewParams);
			}
			else
			{
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
			}
		}
		else
		{
			$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($media['category_id']);

			$viewParams = array(
				'media' => $media,
				'like' => $existingLike,
				'breadCrumbs' => array_reverse($this->getModelFromCache('EWRmedio_Model_Lists')->getCrumbs($category)),
			);

			return $this->responseView('EWRmedio_ViewPublic_MediaLike', 'EWRmedio_MediaLike', $viewParams);
		}
	}

	public function actionReport()
	{
		if (!$this->perms['report']) { return $this->responseNoPermission(); }

		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_request->isPost())
		{
			$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$message)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$this->getModelFromCache('XenForo_Model_Report')->reportContent('media', $media, $message);

			$controllerResponse = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
			$controllerResponse->redirectMessage = new XenForo_Phrase('thank_you_for_reporting_this_message');
			return $controllerResponse;
		}

		$viewParams = array(
			'media' => $media,
		);

		return $this->responseView('EWRmedio_ViewPublic_MediaReport', 'EWRmedio_MediaReport', $viewParams);
	}

	public function actionComments()
	{
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_commentcount;
		$count = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentCount($media);

		$viewParams = array(
			'perms' => $this->perms,
			'start' => $start,
			'stop' => $stop,
			'media' => $media,
			'count' => $count,
			'comments' => $this->getModelFromCache('EWRmedio_Model_Comments')->getComments($media, $start, $stop),
		);

		return $this->responseView('EWRmedio_ViewPublic_MediaComments', 'EWRmedio_MediaComments', $viewParams);
	}

	public function actionPostComment()
	{
		if (!$this->perms['comment']) { return $this->responseNoPermission(); }

		$this->_assertPostOnly();

		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);

		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$input['username'] = $this->_input->filterSingle('username', XenForo_Input::STRING);
		$input['message'] = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$this->getModelFromCache('EWRmedio_Model_Comments')->postComment($input, $media);
		$this->getModelFromCache('EWRmedio_Model_Media')->updateViews($media);

		if ($this->_noRedirect())
		{
			return $this->responseReroute(__CLASS__, 'Comments');
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
	}
	
	public function actionWatchConfirm()
	{
		if (!XenForo_Visitor::getUserId()) { return $this->responseNoPermission(); }
		
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);
		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}
		
		$mediaWatch = $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getUserMediaWatchByMediaId(XenForo_Visitor::getUserId(), $media['media_id']);

		$viewParams = array(
			'media' => $media,
			'mediaWatch' => $mediaWatch,
		);

		return $this->responseView('EWRmedio_ViewPublic_MediaWatch', 'EWRmedio_MediaWatch', $viewParams);
	}
	
	public function actionWatch()
	{
		if (!XenForo_Visitor::getUserId()) { return $this->responseNoPermission(); }
		
		$this->_assertPostOnly();
		
		$mediaID = $this->_input->filterSingle('media_id', XenForo_Input::UINT);
		if (!$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_input->filterSingle('stop', XenForo_Input::STRING))
		{
			$newState = '';
		}
		else if ($this->_input->filterSingle('email_subscribe', XenForo_Input::UINT))
		{
			$newState = 'watch_email';
		}
		else
		{
			$newState = 'watch_no_email';
		}
		
		$this->getModelFromCache('EWRmedio_Model_MediaWatch')->setMediaWatchState(XenForo_Visitor::getUserId(), $mediaID, $newState);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('media', $media),
			null,
			array('linkPhrase' => ($newState ? new XenForo_Phrase('unwatch_media') : new XenForo_Phrase('watch_media')))
		);
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
		$mediaIDs = array();
		foreach ($activities AS $activity)
		{
			if (!empty($activity['params']['media_id']))
			{
				$mediaIDs[$activity['params']['media_id']] = $activity['params']['media_id'];
			}
		}

		$mediaData = array();
		if ($mediaIDs)
		{
			$mediaModel = XenForo_Model::create('EWRmedio_Model_Media');
			$medias = $mediaModel->getMediasByIDs($mediaIDs);

			foreach ($medias AS $media)
			{
				$mediaData[$media['media_id']] = array(
					'title' => $media['media_title'],
					'url' => XenForo_Link::buildPublicLink('media', $media)
				);
			}
		}

        $output = array();
        foreach ($activities as $key => $activity)
		{
			$media = false;
			if (!empty($activity['params']['media_id']))
			{
				$mediaID = $activity['params']['media_id'];
				if (isset($mediaData[$mediaID]))
				{
					$media = $mediaData[$mediaID];
				}
			}

			if ($media)
			{
				$output[$key] = array(new XenForo_Phrase('viewing_media'), $media['title'], $media['url'], false);
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

		if (!$this->perms['view']) { throw $this->getNoPermissionResponseException(); }
	}
}