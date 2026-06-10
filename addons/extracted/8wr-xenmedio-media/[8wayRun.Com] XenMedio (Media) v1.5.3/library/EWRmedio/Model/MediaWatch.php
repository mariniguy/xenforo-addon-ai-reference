<?php

class EWRmedio_Model_MediaWatch extends XenForo_Model
{
	public function getDefaultWatchByUserId($userID)
	{
		if (!$user = $this->_getDb()->fetchRow("
			SELECT media_watch_state
				FROM EWRmedio_users
			WHERE user_id = ?
		", $userID))
		{
			return 'watch_email';
		}
		
		return $user['media_watch_state'];
	}
	
	public function setDefaultWatchByUserId($userID, $state)
	{
		$this->_getDb()->query('
			INSERT INTO EWRmedio_users
				(user_id, media_watch_state)
			VALUES
				(?, ?)
			ON DUPLICATE KEY UPDATE media_watch_state = VALUES(media_watch_state)
		', array($userID, $state));
		
		return true;
	}

	public function getUserMediaWatchByMediaId($userID, $mediaID)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
				FROM EWRmedio_watch
			WHERE user_id = ?
				AND media_id = ?
		', array($userID, $mediaID));
	}
	
	public function getUserMediaWatchByMediaIds($userID, array $mediaIDs)
	{
		if (!$mediaIDs)
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM EWRmedio_watch
			WHERE user_id = ?
				AND media_id IN (' . $this->_getDb()->quote($mediaIDs) . ')
		', 'media_id', $userID);
	}
	
	public function getUsersWatchingMedia($mediaID)
	{
		$autoReadDate = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);
		
		return $this->fetchAllKeyed('
			SELECT EWRmedio_watch.*, EWRmedio_read.*, xf_user.*,
				GREATEST(COALESCE(EWRmedio_read.media_read_date, 0), ' . $autoReadDate . ') AS media_read_date
			FROM EWRmedio_watch
				INNER JOIN xf_user ON (xf_user.user_id = EWRmedio_watch.user_id AND xf_user.user_state = \'valid\' AND xf_user.is_banned = 0)
				LEFT JOIN EWRmedio_read ON (EWRmedio_read.media_id = EWRmedio_watch.media_id AND EWRmedio_read.user_id = xf_user.user_id)
			WHERE EWRmedio_watch.media_id = ?
		', 'user_id', array($mediaID));
	}
	
	public function getMediaWatchedByUser($userID, $start, $stop, $newOnly)
	{
		if ($newOnly)
		{
			$cutoff = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);
			$newOnlyClause = '
				AND EWRmedio_media.last_comment_date > ' . $cutoff . '
				AND EWRmedio_media.last_comment_date > COALESCE(EWRmedio_read.media_read_date, 0)
			';
		}
		else
		{
			$newOnlyClause = '';
		}
		
		$start = ($start - 1) * $stop;
		
		$medias = $this->_getDb()->fetchAll("
			SELECT EWRmedio_watch.*, EWRmedio_media.*
			FROM EWRmedio_watch
				INNER JOIN EWRmedio_media ON (EWRmedio_media.media_id = EWRmedio_watch.media_id)
				LEFT JOIN EWRmedio_read ON (EWRmedio_watch.media_id = EWRmedio_read.media_id AND EWRmedio_read.user_id = ?)
			WHERE EWRmedio_watch.user_id = ?
				AND EWRmedio_media.media_state = 'visible'
				$newOnlyClause
			ORDER BY EWRmedio_media.media_date DESC, EWRmedio_media.media_id DESC
			LIMIT ?, ?
		", array($userID, $userID, $start, $stop));

		foreach ($medias AS &$media)
		{
			$media['lastCommentInfo'] = array(
				'post_date' => $media['last_comment_date'],
				'post_id' => $media['last_comment_id'],
				'user_id' => $media['last_comment_user_id'],
				'username' => $media['last_comment_username']
			);
			$media = $this->getModelFromCache('EWRmedio_Model_Media')->getDuration($media);
		}

        return $medias;
	}
	
	public function countMediaWatchedByUser($userID)
	{
		$count = $this->_getDb()->fetchRow("
			SELECT COUNT(*) AS total
			FROM EWRmedio_watch
				INNER JOIN EWRmedio_media ON (EWRmedio_media.media_id = EWRmedio_watch.media_id)
			WHERE EWRmedio_watch.user_id = ?
				AND EWRmedio_media.media_state = 'visible'
		", array($userID));

		return $count['total'];
	}
	
	public function setMediaWatchState($userID, $mediaID, $state, $overWrite = true)
	{
		$mediaWatch = $this->getUserMediaWatchByMediaId($userID, $mediaID);
		
		if ($mediaWatch && !$overWrite)
		{
			return true;
		}

		switch ($state)
		{
			case 'watch_email':
			case 'watch_no_email':
				$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_MediaWatch');
				if ($mediaWatch)
				{
					$dw->setExistingData($mediaWatch, true);
				}
				else
				{
					$dw->set('user_id', $userID);
					$dw->set('media_id', $mediaID);
				}
				$dw->set('email_subscribe', ($state == 'watch_email' ? 1 : 0));
				$dw->save();
				return true;

			case '':
				if ($mediaWatch)
				{
					$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_MediaWatch');
					$dw->setExistingData($mediaWatch, true);
					$dw->delete();
				}
				return true;

			default:
				return false;
		}
	}
}