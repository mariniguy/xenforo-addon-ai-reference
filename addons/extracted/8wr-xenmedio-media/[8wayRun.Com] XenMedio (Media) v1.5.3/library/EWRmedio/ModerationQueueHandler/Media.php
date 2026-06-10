<?php

class EWRmedio_ModerationQueueHandler_Media extends XenForo_ModerationQueueHandler_Abstract
{
	public function getVisibleModerationQueueEntriesForUser(array $contentIds, array $viewingUser)
	{
		$mediaModel = XenForo_Model::create('EWRmedio_Model_Media');
		$medias = $mediaModel->getMediasByIDs($contentIds);

		$output = array();
		foreach ($medias AS $media)
		{
			$output[$media['media_id']] = array(
				'message' => $media['media_description'],
				'user' => array(
					'user_id' => $media['user_id'],
					'username' => $media['username']
				),
				'title' => $media['media_title'],
				'link' => XenForo_Link::buildPublicLink('media', $media),
				'contentTypeTitle' => new XenForo_Phrase('media')
			);
		}

		return $output;
	}

	public function approveModerationQueueEntry($contentId, $message, $title)
	{
		$queueModel = XenForo_Model::create('XenForo_Model_ModerationQueue');
		$mediaModel = XenForo_Model::create('EWRmedio_Model_Media');
		$media = $mediaModel->getMediaByID($contentId);

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($contentId);
		$dw->set('media_state', 'visible');
		$dw->set('media_description', $message);
		$dw->save();

		$thread = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
		if ($thread->setExistingData($media['thread_id']))
		{
			$thread->set('discussion_state', 'visible');

			if ($thread->save())
			{
				$post = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post', XenForo_DataWriter::ERROR_SILENT);
				$post->setExistingData($thread->get('first_post_id'));
				$post->set('message_state', 'visible');
				$post->save();
			}

			$queueModel->deleteFromModerationQueue('thread', $media['thread_id']);
		}

		return $queueModel->deleteFromModerationQueue('media', $contentId);
	}

	public function deleteModerationQueueEntry($contentId)
	{
		$queueModel = XenForo_Model::create('XenForo_Model_ModerationQueue');
		$threadModel = XenForo_Model::create('XenForo_Model_Thread');
		$mediaModel = XenForo_Model::create('EWRmedio_Model_Media');

		$media = $mediaModel->getMediaByID($contentId);
		$mediaModel->deleteMedia($media);

		if ($threadModel->getThreadById($media['thread_id']))
		{
			$threadModel->deleteThread($media['thread_id'], 'hard');
		}

		return $queueModel->deleteFromModerationQueue('media', $contentId);
	}
}