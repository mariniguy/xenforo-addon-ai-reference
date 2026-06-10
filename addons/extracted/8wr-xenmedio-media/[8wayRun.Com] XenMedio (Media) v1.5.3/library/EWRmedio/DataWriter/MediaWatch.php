<?php

class EWRmedio_DataWriter_MediaWatch extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'EWRmedio_watch' => array(
				'user_id'          => array('type' => self::TYPE_UINT,    'required' => true),
				'media_id'         => array('type' => self::TYPE_UINT,    'required' => true),
				'email_subscribe'  => array('type' => self::TYPE_BOOLEAN, 'default' => 0)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!is_array($data))
		{
			return false;
		}
		else if (isset($data['user_id'], $data['media_id']))
		{
			$userId = $data['user_id'];
			$mediaId = $data['media_id'];
		}
		else if (isset($data[0], $data[1]))
		{
			$userId = $data[0];
			$mediaId = $data[1];
		}
		else
		{
			return false;
		}

		return array('EWRmedio_watch' => $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getUserMediaWatchByMediaId($userId, $mediaId));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'user_id = ' . $this->_db->quote($this->getExisting('user_id'))
			. ' AND media_id = ' . $this->_db->quote($this->getExisting('media_id'));
	}
}