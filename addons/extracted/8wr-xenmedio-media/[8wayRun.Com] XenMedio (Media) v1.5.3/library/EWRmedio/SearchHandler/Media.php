<?php

class EWRmedio_SearchHandler_Media extends XenForo_Search_DataHandler_Abstract
{
	protected $_mediaModel;

	protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
	{
		$metaData = array();

		$indexer->insertIntoIndex(
			'media', $data['media_id'], $data['media_title'], $data['media_description'],
			$data['media_date'], $data['user_id'], 0, $metaData
		);
	}

	protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
	{
		$indexer->updateIndex('media', $data['media_id'], $fieldUpdates);
	}

	protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
	{
		$mediaIDs = array();
		foreach ($dataList as $data)
		{
			$mediaIDs[] = $data['media_id'];
		}

		$indexer->deleteFromIndex('media', $mediaIDs);
	}

	public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
	{
		$mediaIDs = $this->_getMediaModel()->getMediaIDsInRange($lastId, $batchSize);
		if (!$mediaIDs)
		{
			return false;
		}

		$this->quickIndex($indexer, $mediaIDs);

		return max($mediaIDs);
	}

	public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
	{
		$medias = $this->_getMediaModel()->getMediasByIDs($contentIds);
		$mediaIDs = array();

		foreach ($medias as $mediaID => $media)
		{
			$mediaIDs[] = $mediaID;
			$this->insertIntoIndex($indexer, $media);
		}

		return $mediaIDs;
	}

	public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
	{
		return $this->_getMediaModel()->getMediasByIDs($ids);
	}

	public function canViewResult(array $result, array $viewingUser)
	{
		return true;
	}

	public function prepareResult(array $result, array $viewingUser)
	{
		return $result;
	}

	public function getResultDate(array $result)
	{
		return $result['media_date'];
	}

	public function renderResult(XenForo_View $view, array $result, array $search)
	{
		return $view->createTemplateObject('EWRmedio_Search_Result', array(
			'media' => $result,
			'search' => $search,
		));
	}

	public function getSearchContentTypes()
	{
		return array('media');
	}

	public function getSearchFormControllerResponse(XenForo_ControllerPublic_Abstract $controller, XenForo_Input $input, array $viewParams)
	{
		return $controller->responseView('EWRmedio_ViewPublic_Search', 'EWRmedio_Search_Form', $viewParams);
	}

	protected function _getMediaModel()
	{
		if (!$this->_mediaModel)
		{
			$this->_mediaModel = XenForo_Model::create('EWRmedio_Model_Media');
		}

		return $this->_mediaModel;
	}
}