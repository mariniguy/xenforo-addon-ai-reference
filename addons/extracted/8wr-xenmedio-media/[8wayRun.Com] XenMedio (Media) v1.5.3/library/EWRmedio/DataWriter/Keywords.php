<?php

class EWRmedio_DataWriter_Keywords extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_keyword_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_keywords' => array(
				'keyword_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'keyword_text'			=> array('type' => self::TYPE_STRING, 'required' => true),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$wordID = $this->_getExistingPrimaryKey($data, 'keyword_id'))
		{
			return false;
		}

		return array('EWRmedio_keywords' => $this->getModelFromCache('EWRmedio_Model_Keywords')->getKeywordByID($wordID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'keyword_id = ' . $this->_db->quote($this->getExisting('keyword_id'));
	}
}