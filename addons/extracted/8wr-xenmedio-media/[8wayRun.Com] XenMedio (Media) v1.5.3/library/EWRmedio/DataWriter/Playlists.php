<?php

class EWRmedio_DataWriter_Playlists extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_playlist_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_playlists' => array(
				'user_id'				=> array('type' => self::TYPE_UINT, 'required' => true),
				'playlist_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'playlist_date'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'playlist_name'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'playlist_description'	=> array('type' => self::TYPE_STRING, 'required' => true),
				'playlist_media'		=> array('type' => self::TYPE_STRING, 'required' => false, 'default' => ""),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$playID = $this->_getExistingPrimaryKey($data, 'playlist_id'))
		{
			return false;
		}

		return array('EWRmedio_playlists' => $this->getModelFromCache('EWRmedio_Model_Playlists')->getPlaylistByID($playID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'playlist_id = ' . $this->_db->quote($this->getExisting('playlist_id'));
	}

	protected function _preSave()
	{
		if (!$this->_existingData)
		{
			$visitor = XenForo_Visitor::getInstance();
			$this->set('user_id', $visitor['user_id']);
		}
		
		$this->set('playlist_date', XenForo_Application::$time);
	}
}