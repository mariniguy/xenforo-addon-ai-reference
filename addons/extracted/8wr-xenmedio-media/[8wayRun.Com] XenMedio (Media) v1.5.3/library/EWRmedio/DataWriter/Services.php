<?php

class EWRmedio_DataWriter_Services extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_service_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_services' => array(
				'service_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'service_type'			=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'mrss',
					'allowedValues' => array('mrss', 'json', 'html')
				),
				'service_media'			=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'video',
					'allowedValues' => array('video', 'gallery')
				),
				'service_name'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'service_slug'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'service_url'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'service_feed'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'service_regex'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'service_movie'			=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_value2'		=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_thumb'			=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_title'			=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_description'	=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_duration'		=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_keywords'		=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_errors'		=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'null', 'verification' => array('$this', '_verifyNull')),
				'service_parameters'	=> array('type' => self::TYPE_STRING, 'required' => false, 'default' => ''),
				'service_width'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'service_height'		=> array('type' => self::TYPE_UINT, 'required' => true),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$srvID = $this->_getExistingPrimaryKey($data, 'service_id'))
		{
			return false;
		}

		return array('EWRmedio_services' => $this->getModelFromCache('EWRmedio_Model_Services')->getServiceByID($srvID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'service_id = ' . $this->_db->quote($this->getExisting('service_id'));
	}

	protected function _verifyNull(&$field)
	{
		if (!$field) { $field = 'null'; }
		return true;
	}

	protected function _preSave()
	{
		$srvslug = $this->get('service_slug');
		$srvslug = strtolower(trim($srvslug));
		$srvslug = preg_replace('#[^-a-z0-9\s]#', '-', $srvslug);
		$srvslug = preg_replace('#^[-\s]+|[-\s]+$#', '', $srvslug);
		$srvslug = preg_replace('#[-\s]+#', '-', $srvslug);

		$this->set('service_slug', $srvslug);
	}
}