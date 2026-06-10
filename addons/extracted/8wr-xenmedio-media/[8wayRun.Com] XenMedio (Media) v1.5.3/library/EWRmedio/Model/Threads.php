<?php

class EWRmedio_Model_Threads extends XenForo_Model
{
	public function buildThread($media)
	{
		$options = XenForo_Application::get('options');

		if (!in_array($media['media_node'], $options->EWRmedio_autoforum)) { return false; }
		if (!$forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumById($media['media_node']))
		{
			return false;
		}

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$writer->set('user_id', $media['user_id']);
		$writer->set('username', $media['username']);
		$writer->set('title', new XenForo_Phrase(array('create_media_thread_title', 'title' => $media['media_title'])));
		$writer->set('node_id', $forum['node_id']);
		$writer->set('discussion_state', empty($media['bypass']) ? 'moderated' : 'visible');
		if ($options->EWRmedio_autolock)
		{
			$writer->set('discussion_open', 0);
		}
			$postWriter = $writer->getFirstMessageDw();
			$postWriter->set('message', '[medio=full]'.$media['media_id'].'[/medio]');
		$writer->save();

		$thread = $writer->getMergedData();
		$this->getModelFromCache('XenForo_Model_Thread')->markThreadRead($thread, $forum, XenForo_Application::$time);

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($media);
		$dw->set('thread_id', $thread['thread_id']);
		$dw->save();

		return true;
	}

	public function postToThread($comment, $media)
	{
		if (!$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($media['thread_id']))
		{
			$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
			$dw->setExistingData($media);
			$dw->set('thread_id', '0');
			$dw->save();

			return false;
		}

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
		$writer->set('user_id', $comment['user_id']);
		$writer->set('username', $comment['username']);
		$writer->set('message', XenForo_Helper_String::autoLinkBbCode($comment['comment_message']));
		$writer->set('ip_id', $comment['comment_ip']);
		$writer->set('thread_id', $thread['thread_id']);
		$writer->save();

		$post = $writer->getMergedData();

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Comments');
		$dw->setExistingData($comment);
		$dw->set('post_id', $post['post_id']);
		$dw->save();

		return true;
	}

	public function closeThread($threadID)
	{
		if (!$thread = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($threadID))
		{
			return false;
		}

		$visitor = XenForo_Visitor::getInstance();

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
		$writer->set('user_id', $visitor['user_id']);
		$writer->set('username', $visitor['username']);
		$writer->set('message', new XenForo_Phrase('create_media_thread_deleted'));
		$writer->set('thread_id', $thread['thread_id']);
		$writer->save();

		$threadWriter = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$threadWriter->setExistingData($thread['thread_id']);
		$threadWriter->set('discussion_open', 0);
		$threadWriter->save();

		return true;
	}

	public function deletePost($postID)
	{
		if (!$post = $this->getModelFromCache('XenForo_Model_Post')->getPostById($postID))
		{
			return false;
		}

		$this->getModelFromCache('XenForo_Model_Post')->deletePost($post['post_id'], 'hard');
		XenForo_Helper_Cookie::clearIdFromCookie($post['post_id'], 'inlinemod_posts');

		return true;
	}
}