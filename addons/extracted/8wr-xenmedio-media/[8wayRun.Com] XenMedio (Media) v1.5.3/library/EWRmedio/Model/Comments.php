<?php

class EWRmedio_Model_Comments extends XenForo_Model
{
	public function getCommentByID($commentID)
	{
		if (!$comment = $this->_getDb()->fetchRow("
			SELECT EWRmedio_comments.*, EWRmedio_media.media_id, EWRmedio_media.media_title,
				xf_user.*, xf_user.register_date AS userValid,
				IF(NOT ISNULL(xf_user.user_id), xf_user.username, EWRmedio_comments.username) AS username
				FROM EWRmedio_comments
				LEFT JOIN EWRmedio_media ON (EWRmedio_media.media_id = EWRmedio_comments.media_id)
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_comments.user_id)
			WHERE comment_id = ?
		", $commentID))
		{
			return false;
		}

		return $comment;
	}

	public function getCommentsByIDs($commentIDs)
	{
		if (!$comments = $this->fetchAllKeyed("
			SELECT EWRmedio_comments.*, EWRmedio_media.media_id, EWRmedio_media.media_title
				FROM EWRmedio_comments
				LEFT JOIN EWRmedio_media ON (EWRmedio_media.media_id = EWRmedio_comments.media_id)
			WHERE comment_id IN (" . $this->_getDb()->quote($commentIDs) . ")
		", 'comment_id'))
		{
			return array();
		}

		return $comments;
	}

	public function getComments($media, $start, $stop)
	{
		$start = ($start - 1) * $stop;

		if (!$comments = $this->_getDb()->fetchAll("
			SELECT EWRmedio_comments.*, xf_user.*, xf_user.register_date AS userValid,
				IF(NOT ISNULL(xf_user.user_id), xf_user.username, EWRmedio_comments.username) AS username
				FROM EWRmedio_comments
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_comments.user_id)
			WHERE EWRmedio_comments.media_id = ?
			ORDER BY EWRmedio_comments.comment_date DESC
			LIMIT ?, ?
		", array($media['media_id'], $start, $stop)))
		{
			return false;
		}

        return $comments;
	}
	
	public function getNewestCommentsInMediaAfterDate($mediaID, $postDate)
	{
		return $this->fetchAllKeyed($this->_getDb()->limit('
			SELECT EWRmedio_comments.*, xf_user.*,
				IF(NOT ISNULL(xf_user.user_id), xf_user.username, EWRmedio_comments.username) AS username
			FROM EWRmedio_comments
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_comments.user_id)
			WHERE EWRmedio_comments.media_id = ?
				AND EWRmedio_comments.comment_date > ?
			ORDER BY EWRmedio_comments.comment_date DESC
		', 15), 'comment_id', array($mediaID, $postDate));
	}

	public function getCommentCount($media = 0)
	{
		$onlyMedia = $media ? 'WHERE media_id = '.$media['media_id'] : '';

        $count = $this->_getDb()->fetchRow("
			SELECT COUNT(*) AS total
				FROM EWRmedio_comments
			$onlyMedia
		");

		return $count['total'];
	}

	public function postComment($input, $media)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Comments');
		$dw->bulkSet(array(
			'media_id' => $media['media_id'],
			'comment_message' => $input['message'],
			'username' => $input['username'],
		));
		$dw->save();		

		$input = $dw->getMergedData();
		$input['comment_ip'] = $this->getModelFromCache('XenForo_Model_Ip')->logIp($input['user_id'], 'media', $input['comment_id'], 'comment');

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Comments');
		$dw->setExistingData(array('comment_id' => $input['comment_id']));
		$dw->set('comment_ip', $input['comment_ip']);
		$dw->save();
		
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($media);
		$dw->bulkSet(array(
			'last_comment_date' => $input['comment_date'],
			'last_comment_id' => $input['comment_id'],
			'last_comment_user_id' => $input['user_id'],
			'last_comment_username' => $input['username'],
		));
		$dw->save();
		
		$this->getModelFromCache('EWRmedio_Model_MediaWatch')->setMediaWatchState(
			$input['user_id'],
			$input['media_id'],
			$this->getModelFromCache('EWRmedio_Model_MediaWatch')->getDefaultWatchByUserId($input['user_id']),
			false
		);
		
		$autoReadDate = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);
		
		$latestComments = $this->getNewestCommentsInMediaAfterDate($media['media_id'], $autoReadDate);
		list($key) = each($latestComments);
		unset($latestComments[$key]);
		$defaultPreviousComment = reset($latestComments);
		
		if (XenForo_Application::get('options')->EWRmedio_emailIncludeMessage)
		{
			$parseBbCode = true;
			$emailTemplate = 'watched_media_reply_messagetext';
		}
		else
		{
			$parseBbCode = false;
			$emailTemplate = 'watched_media_reply';
		}
		
		$replyUser = $this->getModelFromCache('XenForo_Model_User')->getUserById($input['user_id']);
		$reply = array_merge($replyUser, $input);
			
		$users = $this->getModelFromCache('EWRmedio_Model_MediaWatch')->getUsersWatchingMedia($media['media_id']);
		foreach ($users AS $user)
		{
			if ($user['user_id'] == $input['user_id']) { continue; }
			if ($this->getModelFromCache('XenForo_Model_User')->isUserIgnored($user, $input['user_id'])) { continue; }

			if (!$defaultPreviousComment || !$this->getModelFromCache('XenForo_Model_User')->isUserIgnored($user, $defaultPreviousComment['user_id']))
			{
				$previousComment = $defaultPreviousComment;
			}
			else
			{
				$previousComment = false;
				foreach ($latestComments AS $latestComment)
				{
					if (!$this->getModelFromCache('XenForo_Model_User')->isUserIgnored($user, $latestComment['user_id']))
					{
						$previousComment = $latestComment;
						break;
					}
				}
			}
			
			if ($previousComment['comment_date'] > $user['media_read_date'])
			{
				continue;
			}
			
			if ($user['email_subscribe'] && $user['email'] && $user['user_state'] == 'valid')
			{
				if (!isset($reply['messageText']) && $parseBbCode)
				{
					$bbCodeParserText = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Text'));
					$reply['messageText'] = new XenForo_BbCode_TextWrapper($reply['comment_message'], $bbCodeParserText);

					$bbCodeParserHtml = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('HtmlEmail'));
					$reply['messageHtml'] = new XenForo_BbCode_TextWrapper($reply['comment_message'], $bbCodeParserHtml);
				}

				$mail = XenForo_Mail::create($emailTemplate, array(
					'reply' => $reply,
					'media' => $media,
					'receiver' => $user
				), $user['language_id']);
				$mail->enableAllLanguagePreCache();
				$mail->queue($user['email'], $user['username']);
			}
			
			if (XenForo_Model_Alert::userReceivesAlert($user, 'media_comment', 'insert'))
			{
				XenForo_Model_Alert::alert(
					$user['user_id'],
					$reply['user_id'],
					$reply['username'],
					'media_comment',
					$reply['comment_id'],
					'insert'
				);
			}
		}

		$this->getModelFromCache('EWRmedio_Model_Media')->updateComments($media);

		if (!($media['thread_id'] && $this->getModelFromCache('EWRmedio_Model_Threads')->postToThread($input, $media)))
		{
			$this->getModelFromCache('XenForo_Model_NewsFeed')->publish(
				$input['user_id'],
				$input['username'],
				'media_comment',
				$input['comment_id'],
				'insert'
			);
		}

		return $input;
	}

	public function updateComment($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Comments');
		$dw->setExistingData($input);
		$dw->set('comment_message', $input['message']);
		$dw->save();

		$mediaID = $dw->get('media_id');
		$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($mediaID);

		return $media;
	}

	public function deleteComment($input)
	{
		$this->getModelFromCache('XenForo_Model_NewsFeed')->delete('media_comment', $input['comment_id']);
		$this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('media_comment', $input['comment_id']);

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Comments');
		$dw->setExistingData($input);
		$input['media_id'] = $dw->get('media_id');
		$input['post_id'] = $dw->get('post_id');
		$dw->delete();

		$media = $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($input['media_id']);
		$this->getModelFromCache('EWRmedio_Model_Media')->updateComments($media);

		if ($input['post_id'])
		{
			$this->getModelFromCache('EWRmedio_Model_Threads')->deletePost($input['post_id']);
		}

		return $media;
	}
}