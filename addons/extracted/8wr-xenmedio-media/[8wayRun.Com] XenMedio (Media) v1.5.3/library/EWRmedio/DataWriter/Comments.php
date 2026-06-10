<?php

class EWRmedio_DataWriter_Comments extends XenForo_DataWriter
{
	protected $_existingDataErrorPhrase = 'requested_comment_not_found';

	protected function _getFields()
	{
		return array(
			'EWRmedio_comments' => array(
				'comment_id'		=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'media_id'			=> array('type' => self::TYPE_UINT, 'required' => true, 'verification' => array('$this', '_verifyMedia')),
				'post_id'			=> array('type' => self::TYPE_UINT, 'required' => false, 'default' => '0'),
				'user_id'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'username'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'comment_date'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'comment_message'	=> array('type' => self::TYPE_STRING, 'required' => true),
				'comment_state'		=> array('type' => self::TYPE_STRING, 'required' => true, 'default' => 'visible',
					'allowedValues' => array('visible', 'moderated', 'deleted')
				),
				'comment_ip'		=> array('type' => self::TYPE_UINT, 'required' => false),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$commentID = $this->_getExistingPrimaryKey($data, 'comment_id'))
		{
			return false;
		}

		return array('EWRmedio_comments' => $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentByID($commentID));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'comment_id = ' . $this->_db->quote($this->getExisting('comment_id'));
	}

	protected function _verifyMedia($mediaID)
	{
		if (!$this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID))
		{
			$this->error(new XenForo_Phrase('requested_media_not_found'), 'media_id');
			return false;
		}

		return true;
	}

	protected function _preSave()
	{
		if (!$this->_existingData)
		{
			$visitor = XenForo_Visitor::getInstance();
			$this->set('user_id', $visitor['user_id']);
			if ($visitor['user_id']) { $this->set('username', $visitor['username']); }
			$this->set('comment_date', XenForo_Application::$time);
		}
	}
}