<?php

class EWRmedio_Model_Submit extends XenForo_Model
{
	public function fetchFeedInfo($source)
	{
		$options = XenForo_Application::get('options');
		$source = substr($source, 0, 7) == 'http://' ? $source : 'http://'.$source;
		$services = $this->getModelFromCache('EWRmedio_Model_Services')->getServices();

		foreach ($services AS $service)
		{
			$regexes = explode("\n", $service['service_regex']);

			foreach ($regexes AS $regex)
			{
				if (preg_match('#'.$regex.'#i', $source, $matches))
				{
					$found = true; break 2;
				}
			}
		}

		if (empty($found))
		{
			throw new XenForo_Exception(new XenForo_Phrase('media_url_did_not_match_services'), true);
		}

		$service['service_value'] = $matches['sval1'];
		$media['service_value2'] = !empty($matches['sval2']) ? $matches['sval2'] : '';

		if ($service['service_feed'] && $service['service_feed'] != "null")
		{
			$service['service_feed'] = str_replace('{serviceVAL}', $service['service_value'], $service['service_feed']);
			$service['service_feed'] = str_replace('{serviceVAL2}', $media['service_value2'], $service['service_feed']);
			$service['service_feed'] = str_replace('{external}', $options->boardUrl.'/'.XenForo_Application::$externalDataPath.'/local', $service['service_feed']);

			$client = new Zend_Http_Client($service['service_feed']);
			$feed = $client->request()->getBody();

			if ($service['service_type'] == "mrss")
			{
				$mrss = $this->getModelFromCache('EWRmedio_Model_Xml2Array')->xml2array($feed);
			}
			elseif ($service['service_type'] == "json")
			{
				$json = json_decode($feed, true);
			}
			else
			{
				$html = array('head' => array(), 'meta' => array(), 'link' => array());

				if (preg_match('#<title>(.*)</title>#i',$feed,$matches))
				{
					$html['head']['title'] = $matches[1];
				}

				preg_match_all('#<meta\s+(name|property)="([^"]+)"\s+content="([^"]*)"#i',$feed,$matches);
				foreach ($matches[2] as $key => $value) { $html['meta'][$value] = $matches[3][$key]; }

				preg_match_all('#<meta\s+content="([^"]*)"\s+(name|property)="([^"]+)"#i',$feed,$matches);
				foreach ($matches[3] as $key => $value) { $html['meta'][$value] = $matches[1][$key]; }

				preg_match_all('#<link\s+rel="([^"]+)"\s+(href|src)="([^"]+)"#i',$feed,$matches);
				foreach ($matches[1] as $key => $value) { $html['link'][$value] = $matches[3][$key]; }
			}
		}

		eval("\$errs = $service[service_errors];");
		if ($errs)
		{
			throw new XenForo_Exception($errs, true);
		}

		eval("\$val2 = $service[service_value2];");
		eval("\$thum = $service[service_thumb];");

		if (!$thum)
		{
			throw new XenForo_Exception(new XenForo_Phrase('media_url_did_not_retrieve_valid_data'), true);
		}

		eval("\$titl = $service[service_title];");
		eval("\$desc = $service[service_description];");
		eval("\$dura = $service[service_duration];");

		$options = XenForo_Application::get('options');
		if ($options->EWRmedio_retrievekeywords)
		{
			eval("\$keyw = $service[service_keywords];");
		}
		else
		{
			$keyw = "";
		}

		$service['service_value2'] = $val2;
		$service = $this->getModelFromCache('EWRmedio_Model_Parser')->parseReplace($service);

		$media = array(
			'service_id' => $service['service_id'],
			'service_media' => $service['service_media'],
			'service_value' => $service['service_value'],
			'service_value2' => $service['service_value2'],
			'service_url' => $service['service_url'],
			'service_movie' => $service['service_movie'],
			'service_parameters' => $service['service_parameters'],
			'service_height' => $service['service_height'],
			'service_width' => $service['service_width'],
			'media_thumb' => $thum,
			'media_title' => $titl,
			'media_description' => $desc,
			'media_duration' => $dura,
			'media_keywords' => $keyw,
		);

		$media = $this->getModelFromCache('EWRmedio_Model_Media')->getDuration($media);
		if ($media['service_movie'] == "null") { $media['service_movie'] = false; }

		return $media;
	}
	
	public function fetchYoutubeInfo($url)
	{		
		$client = new Zend_Http_Client($url);
		$feed = $client->request()->getBody();
		$json = json_decode($feed, true);
		$media = array();
		
		$service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceBySlug('youtube');

		if (empty($json['feed']['entry']))
		{
			throw new XenForo_Exception(new XenForo_Phrase('media_url_did_not_retrieve_valid_data'), true);
		}
		
		foreach ($json['feed']['entry'] AS $entry)
		{
			$id = explode('/', $entry['link'][4]['href']);
			$serviceVAL = end($id);
		
			$media[] = array(
				'id' => $serviceVAL,
				'title' => $entry['title']['$t'],
				'desc' => $entry['content']['$t'],
				'source' => $entry['link'][0]['href'],
				'thumb' => $entry['media$group']['media$thumbnail']['2']['url'],
				'exists' => $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByServiceInfo($service['service_id'], $serviceVAL, ''),
			);
		}
		
		return $media;
	}
}