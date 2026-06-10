<?php

class EWRmedio_LikeHandler_Media extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
		$dw->setExistingData($contentId);
		$dw->set('media_likes', $dw->get('media_likes') + $adjustAmount);
		$dw->set('media_like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$mediaModel = XenForo_Model::create('EWRmedio_Model_Media');
		$medias = $mediaModel->getMediasByIDs($contentIds);

		return $medias;
	}

	public function getListTemplateName()
	{
		return 'news_feed_item_media_like';
	}
}