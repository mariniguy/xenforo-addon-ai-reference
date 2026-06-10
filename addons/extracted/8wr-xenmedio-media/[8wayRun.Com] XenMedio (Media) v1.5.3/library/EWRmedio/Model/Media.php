<?php

class EWRmedio_Model_Media extends XenForo_Model
{
	public function getRandom()
	{
		$media = $this->_getDb()->fetchRow("SELECT * FROM EWRmedio_media ORDER BY RAND()");

		return $media;
	}

	public function getMediaByID($mediaID, $ap = true)
	{
		if (!$media = $this->_getDb()->fetchRow("
			SELECT EWRmedio_media.*, EWRmedio_categories.*, EWRmedio_services.*, xf_user.*, EWRmedio_media.service_value2 AS service_value2, EWRmedio_watch.media_id AS media_is_watched,
				IF(xf_user.username IS NULL, EWRmedio_media.username, xf_user.username) AS username, xf_liked_content.like_date
				FROM EWRmedio_media
				LEFT JOIN EWRmedio_categories ON (EWRmedio_categories.category_id = EWRmedio_media.category_id)
				LEFT JOIN EWRmedio_services ON (EWRmedio_services.service_id = EWRmedio_media.service_id)
				LEFT JOIN EWRmedio_watch ON (EWRmedio_media.media_id = EWRmedio_watch.media_id AND EWRmedio_watch.user_id = ?)
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_media.user_id)
				LEFT JOIN xf_liked_content
					ON (xf_liked_content.content_type = 'media'
						AND xf_liked_content.content_id = EWRmedio_media.media_id
						AND xf_liked_content.like_user_id = " .$this->_getDb()->quote(XenForo_Visitor::getUserId()) . ")
			WHERE EWRmedio_media.media_id = ?
		", array(XenForo_Visitor::getUserId(), $mediaID)))
		{
			return false;
		}

		if ($media['likes'] = $media['media_likes'])
		{
			$media['likeUsers'] = unserialize($media['media_like_users']);
		}

		$media = $this->getDuration($media);
		$media = $this->getModelFromCache('EWRmedio_Model_Parser')->parseReplace($media, $ap);

		if ($media['service_movie'] == "null") { $media['service_movie'] = false; }

        return $media;
	}

	public function getMediaIDsInRange($mediaID, $limit)
	{
		return $this->_getDb()->fetchCol($this->_getDb()->limit('
			SELECT media_id
			FROM EWRmedio_media
			WHERE media_id > ?
			ORDER BY media_id
		', $limit), $mediaID);
	}

	public function getMediasByIDs($mediaIDs)
	{
		if (!$medias = $this->fetchAllKeyed("
			SELECT *, IF(xf_user.username IS NULL, EWRmedio_media.username, xf_user.username) AS username
				FROM EWRmedio_media
				LEFT JOIN EWRmedio_categories ON (EWRmedio_categories.category_id = EWRmedio_media.category_id)
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_media.user_id)
			WHERE media_id IN (" . $this->_getDb()->quote($mediaIDs) . ")
		", 'media_id'))
		{
			return array();
		}

        return $medias;
	}

	public function getMediaByThread($threadID)
	{
		if (!$media = $this->_getDb()->fetchRow("SELECT * FROM EWRmedio_media WHERE thread_id = ?", $threadID))
		{
			return false;
		}

        return $media;
	}

	public function getMediaByServiceInfo($serviceID, $serviceVAL, $serviceVAL2)
	{
		if (!$media = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_media
			WHERE service_id = ? AND service_value = ? AND service_value2 = ?
		", array($serviceID, $serviceVAL, $serviceVAL2)))
		{
			return false;
		}

        return $media;
	}

	public function getKeywordLinks($media)
	{
		$keywords = $this->_getDb()->fetchAll("
			SELECT EWRmedio_keywords.*, EWRmedio_keylinks.*
				FROM EWRmedio_keywords
				LEFT JOIN EWRmedio_keylinks ON (EWRmedio_keylinks.keyword_id = EWRmedio_keywords.keyword_id)
			WHERE EWRmedio_keylinks.media_id = ?
			ORDER BY EWRmedio_keywords.keyword_text ASC
		", $media['media_id']);

		return $keywords;
	}

	public function getKeywordNolinks($media)
	{   
	//	$keywords = $this->_getDb()->fetchAll("SELECT * FROM EWRmedio_keywords ORDER BY keyword_text ASC");

		$keywords = $this->_getDb()->fetchAll("
			SELECT EWRmedio_keywords.*
				FROM EWRmedio_keywords
				LEFT JOIN EWRmedio_keylinks ON (EWRmedio_keylinks.keyword_id = EWRmedio_keywords.keyword_id AND EWRmedio_keylinks.media_id = ?)
			WHERE EWRmedio_keylinks.media_id IS NULL
		", $media['media_id']);

		return $keywords;
	}

	public function getDuration($media)
	{
		if ($media['media_duration'] == 0)
		{
			$media['media_hours'] = "0";
			$media['media_minutes'] = "0";
			$media['media_seconds'] = "00";
		}
		else
		{
			$media['media_hours'] = floor($media['media_duration'] / 3600);
			$media['media_minutes'] = floor(($media['media_duration'] % 3600) / 60);
			$media['media_seconds'] = $media['media_duration'] % 60;

			if ($media['media_hours']) { $media['media_minutes'] = str_pad($media['media_minutes'], 2, "0", STR_PAD_LEFT); } 
			$media['media_seconds'] = str_pad($media['media_seconds'], 2, "0", STR_PAD_LEFT);
		}

		return $media;
	}

	public function updateMedia($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');

		if (!empty($input['media_id']) && $media = $this->getMediaByID($input['media_id']))
		{
			$dw->setExistingData($media);
		}
		else
		{
			$dw->bulkSet(array(
				'service_id' => $input['service_id'],
				'service_value' => $input['service_value'],
				'service_value2' => $input['service_value2']
			));
		}

		$dw->bulkSet(array(
			'media_state' => empty($input['bypass']) ? 'moderated' : 'visible',
			'category_id' => $input['category_id'],
			'media_title' => $input['media_title'],
			'media_description' => XenForo_Helper_String::autoLinkBbCode($input['media_description']),
			'media_duration' => $input['media_seconds'] + ($input['media_minutes'] * 60) + ($input['media_hours'] * 60 * 60),
			'media_custom1' => implode(',', $input['media_custom1']),
			'media_custom2' => implode(',', $input['media_custom2']),
			'media_custom3' => implode(',', $input['media_custom3']),
			'media_custom4' => implode(',', $input['media_custom4']),
			'media_custom5' => implode(',', $input['media_custom5']),
		));
		$dw->save();
		$input['media_id'] = $dw->get('media_id');
		$input['user_id'] = $dw->get('user_id');
		$input['username'] = $dw->get('username');
		$input['media_date'] = $dw->get('media_date');

		if (!empty($input['media_oldlinks']))
		{
			$this->getModelFromCache('EWRmedio_Model_Keylinks')->deleteKeylinks($input['media_oldlinks'], $input['media_keylinks']);
		}

		if (!empty($input['media_keywords']))
		{
			$newkeys = $this->getModelFromCache('EWRmedio_Model_Keywords')->updateKeywords($input['media_keywords']);
			$this->getModelFromCache('EWRmedio_Model_Keylinks')->updateKeylinks($newkeys, $input);
		}

		$input['media_keywords'] = $this->updateKeywords($input);

		if (empty($media))
		{
			$this->getModelFromCache('EWRmedio_Model_Thumbs')->buildThumb($input['media_id'], $input['media_thumb']);
			
			$this->getModelFromCache('EWRmedio_Model_MediaWatch')->setMediaWatchState(
				$input['user_id'],
				$input['media_id'],
				$this->getModelFromCache('EWRmedio_Model_MediaWatch')->getDefaultWatchByUserId($input['user_id'])
			);

			if (!empty($input['create_thread']))
			{
				$this->getModelFromCache('EWRmedio_Model_Threads')->buildThread($input);
			}
			else
			{
				$this->getModelFromCache('XenForo_Model_NewsFeed')->publish(
					$input['user_id'],
					$input['username'],
					'media',
					$input['media_id'],
					'insert'
				);
			}
		}

		return $input;
	}

	public function alterMedia($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($input);
		$dw->bulkSet(array(
			'service_id' => $input['service_id'],
			'service_value' => $input['service_value'],
			'service_value2' => $input['service_value2'],
			'media_state' => empty($input['bypass']) ? 'moderated' : 'visible',
		));
		$dw->save();
		$input['media_date'] = $dw->get('media_date');

		return $input;
	}

	public function updateKeywords($media)
	{
		$keystring = array();
		$keywords = $this->getKeywordLinks($media);

		foreach ($keywords AS $keyword)
		{
			$keystring[] = $keyword['keyword_text'];
		}
		$keystring = implode(", ", $keystring);

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($media);
		$dw->set('media_keywords', $keystring);
		$dw->save();

		return $keystring;
	}

	public function updateComments($media)
	{
        $count = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentCount($media);

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($media);
		$dw->set('media_comments', $count);
		$dw->save();

		return true;
	}

	public function updateViews($media)
	{
		if (!XenForo_Helper_Cookie::getCookie('EWRmedio_'.$media['media_id']))
		{
			$this->_getDb()->query("
				UPDATE EWRmedio_media
				SET media_views = media_views+1
				WHERE media_id = ?
			", $media['media_id']);

			$media['media_views']++;
		}

		XenForo_Helper_Cookie::setCookie('EWRmedio_'.$media['media_id'], '1', 86400);
		
		$this->standardizeViewingUserReference($viewingUser);
		$userID = $viewingUser['user_id'];
		
		if ($userID)
		{
			$this->_getDb()->query('
				INSERT INTO EWRmedio_read
					(user_id, media_id, media_read_date)
				VALUES
					(?, ?, ?)
				ON DUPLICATE KEY UPDATE media_read_date = VALUES(media_read_date)
			', array($userID, $media['media_id'], XenForo_Application::$time));
		}

		return $media;
	}

	public function deleteMedia($input)
	{
		$contentIds = array($input['media_id']);

		$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes('media', $contentIds);
		$this->getModelFromCache('XenForo_Model_NewsFeed')->delete('media', $input['media_id']);

		$ipEntries = $this->_getDb()->fetchAll("
			SELECT comment_id
				FROM EWRmedio_comments
			WHERE media_id = ?
		", $input['media_id']);

		$comments = array();
		foreach ($ipEntries AS $entry)
		{
			$comments[] = $entry['comment_id'];
			$this->getModelFromCache('XenForo_Model_NewsFeed')->delete('media_comment', $entry['comment_id']);
			$this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('media_comment', $entry['comment_id']);
		}
		$comments = implode(",", $comments);

		if ($comments)
		{
			$this->_getDb()->query("
				DELETE FROM xf_ip
				WHERE content_type = 'media'
					AND action = 'comment'
					AND content_id IN ( $comments )
			");
		}

		$this->_getDb()->query("
			DELETE FROM EWRmedio_keylinks
			WHERE media_id = ?
		", $input['media_id']);

		$this->_getDb()->query("
			DELETE FROM EWRmedio_comments
			WHERE media_id = ?
		", $input['media_id']);

		$this->_getDb()->query("
			DELETE FROM EWRmedio_watch
			WHERE media_id = ?
		", $input['media_id']);

		$this->_getDb()->query("
			DELETE FROM EWRmedio_read
			WHERE media_id = ?
		", $input['media_id']);

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($input);
		$input['thread_id'] = $dw->get('thread_id');
		$dw->delete();

		$this->getModelFromCache('EWRmedio_Model_Thumbs')->deleteThumb($input['media_id']);

		if ($input['thread_id'])
		{
			$this->getModelFromCache('EWRmedio_Model_Threads')->closeThread($input['thread_id']);
		}

		return true;
	}
}