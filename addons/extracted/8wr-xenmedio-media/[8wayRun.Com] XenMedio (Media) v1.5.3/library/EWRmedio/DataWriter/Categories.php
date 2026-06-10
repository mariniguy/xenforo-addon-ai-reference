<?php

class EWRmedio_DataWriter_Categories extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_category_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_categories' => array(
				'category_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'category_name'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'category_description'	=> array('type' => self::TYPE_STRING, 'required' => true),
				'category_order'		=> array('type' => self::TYPE_UINT, 'required' => true, 'default' => 1),
				'category_parent'		=> array('type' => self::TYPE_UINT, 'required' => true, 'default' => 0),
				'category_disabled'		=> array('type' => self::TYPE_UINT, 'required' => true, 'default' => 0),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$catID = $this->_getExistingPrimaryKey($data, 'category_id'))
		{
			return false;
		}

		return array('EWRmedio_categories' => $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($catID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'category_id = ' . $this->_db->quote($this->getExisting('category_id'));
	}
}