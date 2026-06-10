<?php

class EWRmedio_DataWriter_Media extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_media_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_media' => array(
				'media_id'				=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'category_id'			=> array('type' => self::TYPE_UINT, 'required' => true, 'verification' => array('$this', '_verifyCategory')),
				'user_id'				=> array('type' => self::TYPE_UINT, 'required' => true),
				'username'				=> array('type' => self::TYPE_STRING, 'required' => true),
				'thread_id'				=> array('type' => self::TYPE_UINT, 'required' => false, 'default' => '0'),
				'service_id'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'service_value'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'service_value2'		=> array('type' => self::TYPE_STRING, 'required' => false, 'verification' => array('$this', '_verifyUnique')),
				'media_title'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'media_description'		=> array('type' => self::TYPE_STRING, 'required' => true),
				'media_keywords'		=> array('type' => self::TYPE_STRING, 'required' => false, 'default' => ''),
				'media_date'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'media_duration'		=> array('type' => self::TYPE_UINT, 'required' => true, 'verification' => array('$this', '_verifyDuration')),
				'media_state'			=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'visible',
					'allowedValues'		=> array('visible', 'moderated', 'deleted')
				),
				'media_likes'			=> array('type' => self::TYPE_UINT, 'required' => false),
				'media_like_users' 		=> array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'media_comments'		=> array('type' => self::TYPE_UINT, 'required' => false),
				'media_views'			=> array('type' => self::TYPE_UINT, 'required' => false),
				'media_custom1'			=> array('type' => self::TYPE_STRING, 'required' => false, 'verification' => array('$this', '_verifyCustom')),
				'media_custom2'			=> array('type' => self::TYPE_STRING, 'required' => false),
				'media_custom3'			=> array('type' => self::TYPE_STRING, 'required' => false),
				'media_custom4'			=> array('type' => self::TYPE_STRING, 'required' => false),
				'media_custom5'			=> array('type' => self::TYPE_STRING, 'required' => false),
				'media_custom_cache'	=> array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'last_comment_date'		=> array('type' => self::TYPE_UINT, 'required' => false),
				'last_comment_id'		=> array('type' => self::TYPE_UINT, 'required' => false),
				'last_comment_user_id'	=> array('type' => self::TYPE_UINT, 'required' => false),
				'last_comment_username'	=> array('type' => self::TYPE_STRING, 'required' => false),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$mediaID = $this->_getExistingPrimaryKey($data, 'media_id'))
		{
			return false;
		}

		return array('EWRmedio_media' => $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'media_id = ' . $this->_db->quote($this->getExisting('media_id'));
	}

	protected function _verifyUnique($serviceVAL2)
	{
		$serviceID = $this->get('service_id');
		$serviceVAL = $this->get('service_value');

		if ($this->getModelFromCache('EWRmedio_Model_Media')->getMediaByServiceInfo($serviceID, $serviceVAL, $serviceVAL2))
		{
			$this->error(new XenForo_Phrase('media_already_exists_in_library'), 'service_value');
			return false;
		}

		return true;
	}

	protected function _verifyCategory($categoryID)
	{
		if (!$this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($categoryID))
		{
			return false;
		}

		return true;
	}

	protected function _verifyDuration($duration)
	{
		if (!$duration)
		{
			return false;
		}

		return true;
	}

	protected function _verifyCustom($duration)
	{
		$this->set('media_custom_cache', 'a:0:{}');

		return true;
	}

	protected function _preSave()
	{
		if (!$this->_existingData)
		{
			$visitor = XenForo_Visitor::getInstance();
			
			$this->bulkSet(array(
				'user_id' => $visitor['user_id'],
				'username' => ($visitor['user_id'] ? $visitor['username'] : $_SERVER['REMOTE_ADDR']),
				'media_date' => XenForo_Application::$time,
				'last_comment_date' => '0',
				'last_comment_id' => '0',
				'last_comment_user_id' => '0',
				'last_comment_username' => '',
			));
		}
	}

	protected function _postSave()
	{
		$this->_updateModerationQueue();
		$this->_indexForSearch();
	}

	protected function _postDelete()
	{
		$dataHandler = $this->getModelFromCache('XenForo_Model_Search')->getSearchDataHandler('media');
		$indexer = new XenForo_Search_Indexer();
		$dataHandler->deleteFromIndex($indexer, $this->getMergedData());

		$this->getModelFromCache('XenForo_Model_ModerationQueue')->deleteFromModerationQueue('media', $this->get('media_id'));
	}

	protected function _updateModerationQueue()
	{
		if (!$this->isChanged('media_state'))
		{
			return;
		}

		if ($this->get('media_state') == 'moderated')
		{
			$this->getModelFromCache('XenForo_Model_ModerationQueue')->insertIntoModerationQueue('media', $this->get('media_id'), $this->get('media_date'));
		}
		else if ($this->getExisting('media_state') == 'moderated')
		{
			$this->getModelFromCache('XenForo_Model_ModerationQueue')->deleteFromModerationQueue('media', $this->get('media_id'));
		}
	}

	protected function _indexForSearch()
	{
		$dataHandler = $this->getModelFromCache('XenForo_Model_Search')->getSearchDataHandler('media');
		$indexer = new XenForo_Search_Indexer();

		if ($this->get('media_state') == 'visible')
		{
			if ($this->getExisting('media_state') != 'visible')
			{
				$dataHandler->insertIntoIndex($indexer, $this->getMergedData());
			}
			else if ($this->isChanged('media_title') || $this->isChanged('media_description'))
			{
				$dataHandler->updateIndex($indexer, $this->getMergedData(), array(
					'title' => $this->get('media_title'),
					'message' => $this->get('media_description')
				));
			}
		}
		else if ($this->isUpdate() && $this->get('media_state') != 'visible' && $this->getExisting('media_state') == 'visible')
		{
			$dataHandler->deleteFromIndex($indexer, $this->getMergedData());
		}
	}
}