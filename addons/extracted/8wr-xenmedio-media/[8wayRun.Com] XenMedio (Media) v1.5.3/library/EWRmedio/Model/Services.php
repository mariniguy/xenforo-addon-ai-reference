<?php

class EWRmedio_Model_Services extends XenForo_Model
{
	public function getServices()
	{
		$services = $this->_getDb()->fetchAll("SELECT * FROM EWRmedio_services ORDER BY service_name");

        return $services;
	}

	public function getServiceByID($srvID)
	{
		if (!$service = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_services
			WHERE service_id = ?
		", $srvID))
		{
			return false;
		}

        return $service;
	}

	public function getServiceBySlug($slug)
	{
		if (!$service = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_services
			WHERE service_slug = ?
		", $slug))
		{
			return false;
		}

		if ($service['service_width'] <= 100)
		{
			$service['service_width'] .= '%';
		}

        return $service;
	}

	public function updateService($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Services');

		if (!empty($input['service_id']) && $service = $this->getServiceByID($input['service_id']))
		{
			$dw->setExistingData($service);
		}
		$dw->bulkSet(array(
			'service_type' => $input['service_type'],
			'service_media' => $input['service_media'],
			'service_name' => $input['service_name'],
			'service_slug' => $input['service_newslug'],
			'service_url' => $input['service_url'],
			'service_feed' => $input['service_feed'],
			'service_regex' => $input['service_regex'],
			'service_movie' => $input['service_movie'],
			'service_value2' => $input['service_value2'],
			'service_thumb' => $input['service_thumb'],
			'service_title' => $input['service_title'],
			'service_description' => $input['service_description'],
			'service_duration' => $input['service_duration'],
			'service_keywords' => $input['service_keywords'],
			'service_errors' => $input['service_errors'],
			'service_parameters' => $input['service_parameters'],
			'service_width' => $input['service_width'],
			'service_height' => $input['service_height'],
		));
		$dw->save();
		$input['service_id'] = $dw->get('service_id');
		$input['service_slug'] = $dw->get('service_slug');

		return $input;
	}

	public function importService($fileName)
	{
		if (!file_exists($fileName) || !is_readable($fileName))
		{
			throw new XenForo_Exception(new XenForo_Phrase('please_enter_valid_file_name_requested_file_not_read'), true);
		}

		$file = new SimpleXMLElement($fileName, null, true);

		if ($file->getName() != 'service')
		{
			throw new XenForo_Exception(new XenForo_Phrase('provided_file_is_not_a_service_xml_file'), true);
		}

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Services');

		if ($service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceBySlug($file->service_slug))
		{
			$dw->setExistingData($service);
		}
		$dw->bulkSet(array(
			'service_type' => $file->service_type,
			'service_media' => $file->service_media,
			'service_name' => $file->service_name,
			'service_slug' => $file->service_slug,
			'service_url' => $file->service_url,
			'service_feed' => $file->service_feed,
			'service_regex' => $file->service_regex,
			'service_movie' => $file->service_movie,
			'service_value2' => $file->service_value2,
			'service_thumb' => $file->service_thumb,
			'service_title' => $file->service_title,
			'service_description' => $file->service_description,
			'service_duration' => $file->service_duration,
			'service_keywords' => $file->service_keywords,
			'service_errors' => $file->service_errors,
			'service_parameters' => $file->service_parameters,
			'service_width' => $file->service_width,
			'service_height' => $file->service_height,
		));
		$dw->save();

		return true;
	}
}