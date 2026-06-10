<?php

class EWRmedio_Template_Helper
{
	public static function getMedioIconUrl($media)
	{
		return XenForo_Application::$externalDataUrl . "/media/$media[media_id].jpg";
	}
}