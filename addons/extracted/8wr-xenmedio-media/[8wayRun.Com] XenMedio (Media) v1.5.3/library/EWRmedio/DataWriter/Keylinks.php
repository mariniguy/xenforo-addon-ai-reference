<?php

class EWRmedio_DataWriter_Keylinks extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_keylink_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_keylinks' => array(
				'keyword_id'	=> array('type' => self::TYPE_UINT, 'required' => true),
				'media_id'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'user_id'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'keylink_id'	=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'keylink_date'	=> array('type' => self::TYPE_UINT, 'required' => true),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$linkID = $this->_getExistingPrimaryKey($data, 'keylink_id'))
		{
			return false;
		}

		return array('EWRmedio_keylinks' => $this->getModelFromCache('EWRmedio_Model_Keylinks')->getKeylinkByID($linkID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'keylink_id = ' . $this->_db->quote($this->getExisting('keylink_id'));
	}

	protected function _preSave()
	{
		if (!$this->_existingData)
		{
			$this->set('user_id', XenForo_Visitor::getUserId());
			$this->set('keylink_date', XenForo_Application::$time);
		}
	}
}