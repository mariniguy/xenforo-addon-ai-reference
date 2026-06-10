<?php

class EWRmedio_NewsFeedHandler_Comments extends XenForo_NewsFeedHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		return $model->getModelFromCache('EWRmedio_Model_Comments')->getCommentsByIDs($contentIds);
	}
}