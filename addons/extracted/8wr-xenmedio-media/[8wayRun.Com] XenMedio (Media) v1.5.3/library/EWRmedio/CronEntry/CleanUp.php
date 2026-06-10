<?php

class EWRmedio_CronEntry_CleanUp
{
	public static function runDailyCleanUp()
	{
		$db = XenForo_Application::getDb();
		
		$readMarkingCutOff = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);
		$db->delete('EWRmedio_read', 'media_read_date < ' . $readMarkingCutOff);
	}
}