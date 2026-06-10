<?php

class EWRmedio_Model_Playlists extends XenForo_Model
{
	public function getPlaylistsCount()
	{
        $count = $this->_getDb()->fetchRow("SELECT COUNT(*) AS total FROM EWRmedio_playlists");

		return $count['total'];
	}
	
	public function getPlaylists($start, $stop)
	{
		$start = ($start - 1) * $stop;
		
		if (!$playlists = $this->_getDb()->fetchAll("
			SELECT EWRmedio_playlists.*, xf_user.*
				FROM EWRmedio_playlists
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_playlists.user_id)
			ORDER BY EWRmedio_playlists.playlist_date DESC,EWRmedio_playlists.playlist_id DESC
			LIMIT ?, ?
		", array($start, $stop)))
		{
			return false;
		}

		foreach ($playlists AS &$playlist)
		{
			$playlist['count'] = 0;
			if ($playlist['playlist_media'])
			{
				$count = explode(",", $playlist['playlist_media']);
				$playlist['count'] = count($count);
				$playlist['media_id'] = $count[0];
			}
		}

        return $playlists;
	}
	
	public function getPlaylistsByUserCount($user)
	{
        $count = $this->_getDb()->fetchRow("
			SELECT COUNT(*) AS total
				FROM EWRmedio_playlists
			WHERE user_id = ?
		", $user['user_id']);

		return $count['total'];
	}
	
	public function getPlaylistsByUser($start, $stop, $user)
	{
		$start = ($start - 1) * $stop;
		
		if (!$playlists = $this->_getDb()->fetchAll("
			SELECT EWRmedio_playlists.*, xf_user.*
				FROM EWRmedio_playlists
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_playlists.user_id)
			WHERE EWRmedio_playlists.user_id = ?
			ORDER BY EWRmedio_playlists.playlist_date DESC,EWRmedio_playlists.playlist_id DESC
			LIMIT ?, ?
		", array($user['user_id'], $start, $stop)))
		{
			return false;
		}

		foreach ($playlists AS &$playlist)
		{
			$playlist['count'] = 0;
			if ($playlist['playlist_media'])
			{
				$count = explode(",", $playlist['playlist_media']);
				$playlist['count'] = count($count);
				$playlist['media_id'] = $count[0];
			}
		}

        return $playlists;
	}

	public function getPlaylistByID($playID)
	{
		if (!$playlist = $this->_getDb()->fetchRow("
			SELECT EWRmedio_playlists.*, xf_user.*
				FROM EWRmedio_playlists
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_playlists.user_id)
			WHERE EWRmedio_playlists.playlist_id = ?
		", $playID))
		{
			return false;
		}

        return $playlist;
	}

	public function getPlaylistsByIDs($playIDs)
	{
		if (!$playlists = $this->fetchAllKeyed("
			SELECT *
				FROM EWRmedio_playlists
			WHERE playlist_id IN (" . $this->_getDb()->quote($playIDs) . ")
		", 'playlist_id'))
		{
			return array();
		}

        return $playlists;
	}

	public function getPlaylistByUserID($userID = 0)
	{
		$userID = $userID ? $userID : XenForo_Visitor::getUserId();

		if (!$playlists = $this->_getDb()->fetchAll("
			SELECT *
				FROM EWRmedio_playlists
			WHERE user_id = ?
			ORDER BY playlist_id DESC
		", $userID))
		{
			return false;
		}

        return $playlists;
	}

	public function getMediaByPlaylist($playlist)
	{
		if (!$playlist['playlist_media']) { return false; }

		$medias = $this->_getDb()->fetchAll("
			SELECT EWRmedio_media.*, EWRmedio_categories.*, EWRmedio_services.*, xf_user.*,
				IF(NOT ISNULL(xf_user.user_id), xf_user.username, EWRmedio_media.username) AS username
				FROM EWRmedio_media
				LEFT JOIN EWRmedio_categories ON (EWRmedio_categories.category_id = EWRmedio_media.category_id)
				LEFT JOIN EWRmedio_services ON (EWRmedio_services.service_id = EWRmedio_media.service_id)
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_media.user_id)
			WHERE EWRmedio_media.media_id IN (".$playlist['playlist_media'].")
			ORDER BY FIELD(EWRmedio_media.media_id, ".$playlist['playlist_media'].")
		");

		foreach ($medias AS &$media)
		{
			$media = $this->getModelFromCache('EWRmedio_Model_Media')->getDuration($media);
		}

        return $medias;
	}

	public function getPlaylistWithMedia($playlist, $media)
	{
		$mediaIDs = explode(',',$playlist['playlist_media']);

		if (in_array($media['media_id'], $mediaIDs))
		{
			$medias = $this->_getDb()->fetchAll("
				SELECT EWRmedio_media.*, EWRmedio_services.service_slug
					FROM EWRmedio_media
					LEFT JOIN EWRmedio_services ON (EWRmedio_services.service_id = EWRmedio_media.service_id)
				WHERE EWRmedio_media.media_id IN (".$playlist['playlist_media'].")
				ORDER BY FIELD(EWRmedio_media.media_id, ".$playlist['playlist_media'].")
			");

			foreach ($medias AS $key => $exists)
			{
				if ($exists['media_id'] == $media['media_id'])
				{
					$now = $key;
					break;
				}
			}
			$playlist['count'] = count($medias);

			if (!empty($medias[$now-1]))
			{
				$playlist['prev'] = $medias[$now-1];
				$playlist['prev'] = $this->getModelFromCache('EWRmedio_Model_Media')->getDuration($playlist['prev']);
			}

			if (!empty($medias[$now+1]))
			{
				$playlist['next'] = $medias[$now+1];
				$playlist['next'] = $this->getModelFromCache('EWRmedio_Model_Media')->getDuration($playlist['next']);
			}
		}
		else
		{
			return false;
		}

		return $playlist;
	}

	public function updatePlaylist($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Playlists');

		if (!empty($input['playlist_id']) && $playlist = $this->getPlaylistByID($input['playlist_id']))
		{
			$dw->setExistingData($playlist);
			$dw->set('playlist_media', implode(',',$input['playlist_media']));
		}
		else
		{
			if (!empty($input['media_id']))
			{
				$dw->set('playlist_media', $input['media_id']);
			}
		}
		
		$dw->bulkSet(array(
			'playlist_name' => $input['playlist_name'],
			'playlist_description' => XenForo_Helper_String::autoLinkBbCode($input['playlist_description']),
		));
		$dw->save();
		
		$input['playlist_id'] = $dw->get('playlist_id');
		$input['playlist_media'] = $dw->get('playlist_media');

		return $input;
	}

	public function deletePlaylist($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Playlists');
		$dw->setExistingData($input);
		$dw->delete();

		return true;
	}
	
	public function addToPlaylist($playlist, $input)
	{
		$mediaIDs = explode(',',$playlist['playlist_media']);
		$mediaIDs[] = $input['media_id'];
		
		foreach ($mediaIDs AS $key => $mediaID)
		{
			if (empty($mediaID))
			{
				unset($mediaIDs[$key]);
			}
		}
		
		$playlist['playlist_media'] = implode(',',$mediaIDs);
		
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Playlists');
		$dw->setExistingData($playlist);
		$dw->set('playlist_media', $playlist['playlist_media']);
		$dw->save();

		return $playlist;
	}
}