<?php

class EWRmedio_Listener_Controller
{
    public static function controller($class, array &$extend)
    {
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Account':
				$extend[] = 'EWRmedio_ControllerPublic_Account';
				break;
			case 'XenForo_ControllerPublic_Thread':
				$extend[] = 'EWRmedio_ControllerPublic_Thread';
				break;
			case 'XenForo_ControllerPublic_Watched':
				$extend[] = 'EWRmedio_ControllerPublic_Watched';
				break;
		}
    }
}