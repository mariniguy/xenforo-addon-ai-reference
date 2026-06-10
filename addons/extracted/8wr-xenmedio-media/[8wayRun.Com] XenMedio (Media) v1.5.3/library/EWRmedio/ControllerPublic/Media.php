<?php

class EWRmedio_ControllerPublic_Media extends XenForo_ControllerPublic_Abstract
{
	public $perms;
	public $slugs;

	public function actionIndex()
	{
		$options = XenForo_Application::get('options');

		$viewParams = array(
			'perms' => $this->perms,
			'recentMedia' => $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList(1, $options->EWRmedio_recentcount, 'date', 'DESC'),
			'popularMedia' => $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList(1, $options->EWRmedio_popularcount, 'popular'),
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_Media', 'EWRmedio_Media', $viewParams);
	}

	public function actionRebuildThumbs()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		$db = XenForo_Application::get('db');
		$start = $this->_input->filterSingle('start', XenForo_Input::UINT);
		$stop = 5;

		if (!$medias = $db->fetchAll("
			SELECT EWRmedio_media.media_id, EWRmedio_media.media_title, EWRmedio_media.service_value, EWRmedio_services.*
				FROM EWRmedio_media
				LEFT JOIN EWRmedio_services ON (EWRmedio_services.service_id = EWRmedio_media.service_id)
			WHERE EWRmedio_media.service_id = '1'
				AND EWRmedio_media.media_id > ?
			ORDER BY media_id ASC
			LIMIT ?
		", array($start, $stop)))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media'));
		}

		foreach ($medias AS &$media)
		{
			$start = $media['media_id'];

			echo $media['media_id']." - ".$media['media_title'];
			echo '<blockquote>';

/*
			if (file_exists(XenForo_Helper_File::getExternalDataPath()."/media/".$media['media_id'].".jpg"))
			{
				echo "<b>ERROR</b> : THUMBNAIL EXISTS - SKIPPING...</blockquote>";
				continue;
			}
*/

			$media['service_feed'] = str_replace('{serviceVAL}', $media['service_value'], $media['service_feed']);
			$client = new Zend_Http_Client($media['service_feed']);
			$feed = $client->request()->getBody();
			$json = json_decode($feed, true);

			eval("\$errs = $media[service_errors];");

			if ($errs)
			{
				echo "<b>ERROR</b> : ".$errs." - WAITING...</blockquote>";
				exit;
			}

			eval("\$val2 = $media[service_value2];");
			eval("\$thum = $media[service_thumb];");

			if (!$thum)
			{
				$this->getModelFromCache('EWRmedio_Model_Media')->deleteMedia($media);
				echo "<b>ERROR</b> : NO THUMB - DELETING MEDIA...</blockquote>";
				continue;
			}

			$this->getModelFromCache('EWRmedio_Model_Thumbs')->buildThumb($media['media_id'], $thum);
			echo '<img src="'.XenForo_Link::buildPublicLink('full:data/media').'/'.$media['media_id'].'.jpg">';
			echo '</blockquote>';
		}

		echo '<meta http-equiv="refresh" content="1;url='.XenForo_Link::buildPublicLink('full:media/rebuild-thumbs').'?start='.($start).'">';
		exit;
	}

	public function actionRss()
	{
		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia(),
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public function actionCategories()
	{
		$viewParams = array(
			'perms' => $this->perms,
			'catList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList(),
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_Categories', 'EWRmedio_Categories', $viewParams);
	}
	
	public function actionPlaylists()
	{		
		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_mediacount;
		$count = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistsCount();

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/playlists', array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media/playlists');
		
		$viewParams = array(
			'perms' => $this->perms,
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'playlists' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylists($start, $stop),
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_Playlists', 'EWRmedio_Playlists', $viewParams);
	}

	public function actionRandom()
	{
		$media = $this->getModelFromCache('EWRmedio_Model_Media')->getRandom();

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
	}

	public function actionSubmit()
	{
		if (!$this->perms['submit']) { return $this->responseNoPermission(); }

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'category_id' => XenForo_Input::UINT,
				'service_id' => XenForo_Input::UINT,
				'service_value' => XenForo_Input::STRING,
				'service_value2' => XenForo_Input::STRING,
				'media_thumb' => XenForo_Input::STRING,
				'media_title' => XenForo_Input::STRING,
				'media_hours' => XenForo_Input::UINT,
				'media_minutes' => XenForo_Input::UINT,
				'media_seconds' => XenForo_Input::UINT,
				'media_keywords' => XenForo_Input::STRING,
				'media_keyarray' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom1' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom2' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom3' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom4' => XenForo_Input::ARRAY_SIMPLE,
				'media_custom5' => XenForo_Input::ARRAY_SIMPLE,
				'media_node' => XenForo_Input::UINT,
				'create_thread' => XenForo_Input::UINT,
				'submit' => XenForo_Input::STRING,
			));
			$input['bypass'] = $this->perms['bypass'];
			$input['media_description'] = $this->getHelper('Editor')->getMessageText('media_description', $this->_input);

			if (!$source = $this->_input->filterSingle('source', XenForo_Input::STRING))
			{
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
		}

		if ($source = $this->_input->filterSingle('source', XenForo_Input::STRING))
		{
			$media = $this->getModelFromCache('EWRmedio_Model_Submit')->fetchFeedInfo($source);
		}

		$options = XenForo_Application::get('options');
		$forums = array();

		foreach ($options->EWRmedio_autoforum AS $forum)
		{
			$forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumById($forum);

			if ($forum && $this->getModelFromCache('XenForo_Model_Forum')->canPostThreadInForum($forum))
			{
				$forums[] = $forum;
			}
		}

		$viewParams = array(
			'captcha' => XenForo_Captcha_Abstract::createDefault(),
			'media' => !empty($media) ? $media : false,
			'customs' => $this->getModelFromCache('EWRmedio_Model_Custom')->getCustomOptions(),
			'forums' => $forums,
			'checked' => $options->EWRmedio_autocheck ? 'checked' : '',
			'fullList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList(),
		);
		
		if (!$options->EWRmedio_newkeywords)
		{
			$viewParams['keywords'] = $this->getModelFromCache('EWRmedio_Model_Keywords')->getAllKeywords();
		}

		return $this->responseView('EWRmedio_ViewPublic_Submit', 'EWRmedio_Submit', $viewParams);
	}
	
	public function actionSubmitYoutube()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }
		
		$viewParams = array();
		
		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'gettype' => XenForo_Input::STRING,
				'channel' => XenForo_Input::STRING,
				'startin' => XenForo_Input::UINT,
				'results' => XenForo_Input::UINT,
			));
			
			switch ($input['gettype'])
			{
				case "playlist":	$url = 'http://gdata.youtube.com/feeds/api/playlists/' . str_replace('PL', '', $input['channel']) . '?alt=json&orderby=published&start-index='.
										$input['startin'] . '&max-results=' . $input['results'];
									break;
				default:			$url = 'http://gdata.youtube.com/feeds/api/videos?alt=json&orderby=published&start-index='.
										$input['startin'] . '&max-results=' . $input['results'] . '&author=' . $input['channel'];
			}
			
			$viewParams = array(
				'input' => $input,
				'customs' => $this->getModelFromCache('EWRmedio_Model_Custom')->getCustomOptions(),
				'fullList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList(),
				'mediaList' => $this->getModelFromCache('EWRmedio_Model_Submit')->fetchYoutubeInfo($url),
				'playlistList' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByUserID(),
			);
		}

		return $this->responseView('EWRmedio_ViewPublic_Submit_Youtube', 'EWRmedio_Submit_Youtube', $viewParams);
	}
	
	public function actionSubmitYoutubeFeed()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		$this->_assertPostOnly();
		
		$input = $this->_input->filter(array(
			'category_id' => XenForo_Input::UINT,
			'playlist_id' => XenForo_Input::UINT,
			'media' => XenForo_Input::ARRAY_SIMPLE,
			'media_custom1' => XenForo_Input::ARRAY_SIMPLE,
			'media_custom2' => XenForo_Input::ARRAY_SIMPLE,
			'media_custom3' => XenForo_Input::ARRAY_SIMPLE,
			'media_custom4' => XenForo_Input::ARRAY_SIMPLE,
			'media_custom5' => XenForo_Input::ARRAY_SIMPLE,
			'submit' => XenForo_Input::STRING,
		));
		
		$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($input['playlist_id']);
		$input['media'] = array_reverse($input['media']);
		
		foreach ($input['media'] AS $source)
		{
			$media = $this->getModelFromCache('EWRmedio_Model_Submit')->fetchFeedInfo($source);
			$media['category_id'] = $input['category_id'];
			$media['media_custom1'] = $input['media_custom1'];
			$media['media_custom2'] = $input['media_custom2'];
			$media['media_custom3'] = $input['media_custom3'];
			$media['media_custom4'] = $input['media_custom4'];
			$media['media_custom5'] = $input['media_custom5'];
			$media['bypass'] = $this->perms['bypass'];
			$media = $this->getModelFromCache('EWRmedio_Model_Media')->updateMedia($media);
			
			if ($playlist)
			{
				$addTo = array('media_id' => $media['media_id']);
				$playlist = $this->getModelFromCache('EWRmedio_Model_Playlists')->addToPlaylist($playlist, $addTo);
			}
		}
		
		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media'));
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