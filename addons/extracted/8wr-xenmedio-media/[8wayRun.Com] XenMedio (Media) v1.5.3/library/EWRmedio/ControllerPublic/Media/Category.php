<?php

class EWRmedio_ControllerPublic_Media_Category extends XenForo_ControllerPublic_Abstract
{
	public $perms;

	public function actionIndex()
	{
		$catID = $this->_input->filterSingle('category_id', XenForo_Input::UINT);

		if (!$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($catID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/categories'));
		}

		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_mediacount;
		$count = $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaCount('category', $category['category_id']);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/category', $category, array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media/category', $category);

		$breadCrumbs = array_reverse($this->getModelFromCache('EWRmedio_Model_Lists')->getCrumbs($category));
		array_pop($breadCrumbs);

		$viewParams = array(
			'perms' => $this->perms,
			'category' => $category,
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList($start, $stop, 'date', 'DESC', 'category', $category['category_id']),
			'breadCrumbs' => $breadCrumbs,
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_CategoryView', 'EWRmedio_CategoryView', $viewParams);
	}

	public function actionRss()
	{
		$catID = $this->_input->filterSingle('category_id', XenForo_Input::UINT);

		if (!$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($catID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/categories'));
		}

		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia(null, 'category', $category['category_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public function actionPodcast()
	{
		$catID = $this->_input->filterSingle('category_id', XenForo_Input::UINT);

		if (!$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($catID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/categories'));
		}

		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia(null, 'category', $category['category_id'], true)
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public function actionEdit()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		$catID = $this->_input->filterSingle('category_id', XenForo_Input::UINT);

		if (!$category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($catID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'category_name' => XenForo_Input::STRING,
				'category_parent' => XenForo_Input::UINT,
				'category_disabled' => XenForo_Input::UINT,
				'submit' => XenForo_Input::STRING,
			));
			$input['category_id'] = $category['category_id'];
			$input['category_description'] = $this->getHelper('Editor')->getMessageText('category_description', $this->_input);

			$this->getModelFromCache('EWRmedio_Model_Categories')->updateCategory($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/admin/categories'));
		}

		$children = array($category['category_id'] => $category);
		$children = $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList($category['category_id'], $children);
		$catList = $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList();

		foreach ($catList AS &$list)
		{
			$list['disabled'] = array_key_exists($list['category_id'], $children) ? true:false;
		}

		$viewParams = array(
			'category' => $category,
			'catList' => $catList,
		);

		return $this->responseView('EWRmedio_ViewPublic_CategoryEdit', 'EWRmedio_CategoryEdit', $viewParams);
	}

	public function actionDelete()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		$catID = $this->_input->filterSingle('category_id', XenForo_Input::UINT);
		$catParent = $this->_input->filterSingle('category_parent', XenForo_Input::UINT);

		if ($category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($catID))
		{
			if ($this->_request->isPost())
			{
				if (!$category['category_parent'] = $this->_input->filterSingle('category_parent', XenForo_Input::UINT))
				{
					throw new XenForo_Exception(new XenForo_Phrase('please_select_a_new_parent_category_node'), true);
				}

				$this->getModelFromCache('EWRmedio_Model_Categories')->deleteCategory($category);
			}
			else
			{
				$children = array($category['category_id'] => $category);
				$children = $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList($category['category_id'], $children);
				$catList = $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList();

				foreach ($catList AS &$list)
				{
					$list['disabled'] = array_key_exists($list['category_id'], $children) ? true:false;
				}

				$viewParams = array(
					'category' => $category,
					'catList' => $catList,
				);

				return $this->responseView('EWRmedio_ViewPublic_CategoryDelete', 'EWRmedio_CategoryDelete', $viewParams);
			}
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/admin/categories'));
	}

	public function actionCreate()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'category_name' => XenForo_Input::STRING,
				'category_parent' => XenForo_Input::UINT,
				'category_disabled' => XenForo_Input::UINT,
				'submit' => XenForo_Input::STRING,
			));
			$input['category_description'] = $this->getHelper('Editor')->getMessageText('category_description', $this->_input);

			$this->getModelFromCache('EWRmedio_Model_Categories')->updateCategory($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/admin/categories'));
		}

		$viewParams = array(
			'catList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList(),
		);

		return $this->responseView('EWRmedio_ViewPublic_CategoryCreate', 'EWRmedio_CategoryCreate', $viewParams);
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
		$catIDs = array();
		foreach ($activities AS $activity)
		{
			if (!empty($activity['params']['category_id']))
			{
				$catIDs[$activity['params']['category_id']] = $activity['params']['category_id'];
			}
		}

		$categoryData = array();
		if ($catIDs)
		{
			$catModel = XenForo_Model::create('EWRmedio_Model_Categories');
			$categories = $catModel->getCategoriesByIDs($catIDs);

			foreach ($categories AS $category)
			{
				$categoryData[$category['category_id']] = array(
					'title' => $category['category_name'],
					'url' => XenForo_Link::buildPublicLink('media/category', $category)
				);
			}
		}

        $output = array();
        foreach ($activities as $key => $activity)
		{
			$category = false;
			if (!empty($activity['params']['category_id']))
			{
				$catID = $activity['params']['category_id'];
				if (isset($categoryData[$catID]))
				{
					$category = $categoryData[$catID];
				}
			}

			if ($category)
			{
				$output[$key] = array(new XenForo_Phrase('viewing_media_library'), $category['title'], $category['url'], false);
			}
			else
			{
				$output[$key] = new XenForo_Phrase('viewing_media_library');
			}
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