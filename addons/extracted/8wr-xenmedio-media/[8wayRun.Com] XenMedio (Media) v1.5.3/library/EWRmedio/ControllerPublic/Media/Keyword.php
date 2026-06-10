<?php

class EWRmedio_ControllerPublic_Media_Keyword extends XenForo_ControllerPublic_Abstract
{
	public $perms;

	public function actionIndex()
	{
		$keywordSlug = $this->_input->filterSingle('keyword_text', XenForo_Input::STRING);

		if (!$keyword = $this->getModelFromCache('EWRmedio_Model_Keywords')->getKeywordByText($keywordSlug))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_mediacount;
		$count = $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaCount('keyword', $keyword['keyword_id']);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/keyword', $keyword, array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media/keyword', $keyword);

		$viewParams = array(
			'perms' => $this->perms,
			'keyword' => $keyword,
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList($start, $stop, 'date', 'DESC', 'keyword', $keyword['keyword_id']),
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_KeywordView', 'EWRmedio_KeywordView', $viewParams);
	}

	public function actionRss()
	{
		$keywordSlug = $this->_input->filterSingle('keyword_text', XenForo_Input::STRING);

		if (!$keyword = $this->getModelFromCache('EWRmedio_Model_Keywords')->getKeywordByText($keywordSlug))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia(null, 'keyword', $keyword['keyword_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public function actionCreate()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }
		
		$this->_assertPostOnly();
		$this->getModelFromCache('EWRmedio_Model_Keywords')->updateKeywords($this->_input->filterSingle('media_keywords', XenForo_Input::STRING));

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/admin/keywords'));
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
        $output = array();
        foreach ($activities as $key => $activity)
		{
			$output[$key] = new XenForo_Phrase('viewing_media_library');
        }

        return $output;
	}

	protected function _preDispatch($action)
	{
		parent::_preDispatch($action);

		$this->perms = $this->getModelFromCache('EWRmedio_Model_Perms')->getPermissions();

		if (!$this->perms['browse']) { throw $this->getNoPermissionResponseException(); }
	}
}