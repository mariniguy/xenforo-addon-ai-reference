<?php

class EWRmedio_Model_Parser extends XenForo_Model
{
	public function parseSidebar()
	{
		$options = XenForo_Application::get('options');

		$sidebar['categories'] = $this->getModelFromCache('EWRmedio_Model_Lists')->getCategoryList();
		$sidebar['users'] = $this->getModelFromCache('EWRmedio_Model_Lists')->getUserList();
		$sidebar['keywords'] = $this->getModelFromCache('EWRmedio_Model_Keywords')->getKeywordCloud($options->EWRmedio_cloudcount, $options->EWRmedio_mincloud, $options->EWRmedio_maxcloud);
		
		if ($options->EWRmedio_animatedcloud && $sidebar['keywords'])
		{
			$sidebar['animated'] = $this->getModelFromCache('EWRmedio_Model_Keywords')->getAnimatedCloud($sidebar['keywords']);
		}

		$sidebar['stats']['media'] = $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaCount();
		$sidebar['stats']['categories'] = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryCount();
		$sidebar['stats']['comments'] = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentCount();
		$sidebar['stats']['likes'] = $this->getLikeCount();
		$sidebar['stats']['views'] = $this->getViewCount();

        return $sidebar;
	}

	public function parseReplace($replace, $ap = true)
	{
		$options = XenForo_Application::get('options');
		
		$external = $options->boardUrl.'/'.XenForo_Application::$externalDataPath.'/local';
		$scriptjw = $options->boardUrl.'/styles/8wayrun/jw';

		if ($replace['service_width'] <= 100)
		{
			$replace['service_width'] .= '%';
		}

		$valuesOld = array(
			"{serviceVAL}", "{serviceVAL2}", "{domain}",
			"{external}", "{scriptjw}", "{w}", "{h}",
			"{ap10}", "{apTF}", "{apYN}", "{ap10r}", "{apTFr}", "{apYNr}"
		);

		$valuesNew = array(
			$replace['service_value'], $replace['service_value2'],
			$options->boardUrl, $external, $scriptjw,
			$replace['service_width'], $replace['service_height']
		);

		if ($options->EWRmedio_autoplay && $ap)
		{
			$valuesNew[] = "1";
			$valuesNew[] = "true";
			$valuesNew[] = "yes";
			$valuesNew[] = "0";
			$valuesNew[] = "false";
			$valuesNew[] = "no";
		}
		else
		{
			$valuesNew[] = "0";
			$valuesNew[] = "false";
			$valuesNew[] = "no";
			$valuesNew[] = "1";
			$valuesNew[] = "true";
			$valuesNew[] = "yes";
		}

		$replace['service_url'] = str_replace($valuesOld, $valuesNew, $replace['service_url']);
		$replace['service_movie'] = str_replace($valuesOld, $valuesNew, $replace['service_movie']);
		$replace['service_parameters'] = str_replace($valuesOld, $valuesNew, $replace['service_parameters']);
		$replace['content_loc'] = str_replace($valuesOld, $valuesNew, '{external}/{serviceVAL}');

		if ($replace['service_movie'] != 'null')
		{
			$split = explode('?', $replace['service_movie']);

			if (!empty($split[1]))
			{
				$replace['service_flashvars'] = $split[1] . (isset($split[2]) ? $split[2] : '');
			}
		}

		return $replace;
	}

	public function getLikeCount()
	{
        $count = $this->_getDb()->fetchRow("
			SELECT SUM(media_likes) AS total
				FROM EWRmedio_media
		");

		return $count['total'];
	}

	public function getViewCount()
	{
        $count = $this->_getDb()->fetchRow("
			SELECT SUM(media_views) AS total
				FROM EWRmedio_media
		");

		return $count['total'];
	}
}