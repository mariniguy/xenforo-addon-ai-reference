<?php

class EWRmedio_AlertHandler_Media extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		return $model->getModelFromCache('EWRmedio_Model_Media')->getMediasByIDs($contentIds);
	}
}