<?php

class EWRmedio_ControllerPublic_Media_Service extends XenForo_ControllerPublic_Abstract
{
	public $perms;

	public function actionIndex()
	{
		$serviceSlug = $this->_input->filterSingle('service_slug', XenForo_Input::STRING);

		if (!$service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceBySlug($serviceSlug))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$options = XenForo_Application::get('options');
		$start = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$stop = $options->EWRmedio_mediacount;
		$count = $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaCount('service', $service['service_id']);

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('media/service', $service, array('page' => $start)));
		$this->canonicalizePageNumber($start, $stop, $count, 'media/service', $service);

		$viewParams = array(
			'perms' => $this->perms,
			'service' => $service,
			'start' => $start,
			'stop' => $stop,
			'count' => $count,
			'mediaList' => $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList($start, $stop, 'date', 'DESC', 'service', $service['service_id']),
			'sidebar' => $this->getModelFromCache('EWRmedio_Model_Parser')->parseSidebar(),
		);

		return $this->responseView('EWRmedio_ViewPublic_ServiceView', 'EWRmedio_ServiceView', $viewParams);
	}

	public function actionRss()
	{
		$serviceSlug = $this->_input->filterSingle('service_slug', XenForo_Input::STRING);

		if (!$service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceBySlug($serviceSlug))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		$this->_routeMatch->setResponseType('rss');

		$viewParams = array(
			'rss' => $this->getModelFromCache('EWRmedio_Model_Sitemaps')->getRSSbyMedia(null, 'service', $service['service_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_RSS', '', $viewParams);
	}

	public function actionEdit()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		$serviceSlug = $this->_input->filterSingle('service_slug', XenForo_Input::STRING);

		if (!$service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceBySlug($serviceSlug))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/admin/services'));
		}

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'service_type' => XenForo_Input::STRING,
				'service_media' => XenForo_Input::STRING,
				'service_name' => XenForo_Input::STRING,
				'service_newslug' => XenForo_Input::STRING,
				'service_url' => XenForo_Input::STRING,
				'service_feed' => XenForo_Input::STRING,
				'service_regex' => XenForo_Input::STRING,
				'service_movie' => XenForo_Input::STRING,
				'service_value2' => XenForo_Input::STRING,
				'service_thumb' => XenForo_Input::STRING,
				'service_title' => XenForo_Input::STRING,
				'service_description' => XenForo_Input::STRING,
				'service_duration' => XenForo_Input::STRING,
				'service_keywords' => XenForo_Input::STRING,
				'service_errors' => XenForo_Input::STRING,
				'service_parameters' => XenForo_Input::STRING,
				'service_width' => XenForo_Input::UINT,
				'service_height' => XenForo_Input::UINT,
				'submit' => XenForo_Input::STRING,
				'reload' => XenForo_Input::STRING,
			));
			$input['service_id'] = $service['service_id'];
			$input = $this->getModelFromCache('EWRmedio_Model_Services')->updateService($input);

			if (!empty($input['reload']))
			{
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/service/edit', $input));
			}
			else
			{
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/admin/services'));
			}
		}

		$viewParams = array(
			'service' => $service,
		);

		return $this->responseView('EWRmedio_ViewPublic_ServiceEdit', 'EWRmedio_ServiceEdit', $viewParams);
	}

	public function actionExport()
	{
		if (!$this->perms['admin']) { return $this->responseNoPermission(); }

		if (XenForo_Application::autoload('EWRmedio_XML_Premium'))
		{
			$serviceSlug = $this->_input->filterSingle('service_slug', XenForo_Input::STRING);

			if (!$service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceBySlug($serviceSlug))
			{
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media/admin/services'));
			}

			$this->_routeMatch->setResponseType('xml');

			$viewParams = array(
				'service' => $service,
				'xml' => $this->getModelFromCache('EWRmedio_XML_Premium')->exportService($service),
			);

			return $this->responseView('EWRmedio_ViewPublic_ServiceExport', '', $viewParams);
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media/admin/services'));
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