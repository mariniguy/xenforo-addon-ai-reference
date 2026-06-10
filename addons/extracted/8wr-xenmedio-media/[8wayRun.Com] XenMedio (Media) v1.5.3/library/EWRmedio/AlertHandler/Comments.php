<?php

class EWRmedio_AlertHandler_Comments extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		return $model->getModelFromCache('EWRmedio_Model_Comments')->getCommentsByIDs($contentIds);
	}
}