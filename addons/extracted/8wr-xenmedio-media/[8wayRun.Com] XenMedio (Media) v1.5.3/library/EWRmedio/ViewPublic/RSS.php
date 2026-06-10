<?php

class EWRmedio_ViewPublic_RSS extends XenForo_ViewPublic_Base
{
	public function renderRss()
	{
		return $this->_params['rss']->saveXml();
	}
}