<?php

class EWRmedio_Model_Perms extends XenForo_Model
{
	public function getPermissions(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$perms['browse'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canBrowse') ? true : false);
		$perms['view'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canView') ? true : false);
		$perms['report'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canReport') ? true : false);
		$perms['like'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canLike') ? true : false);
		$perms['comment'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canComment') ? true : false);
		$perms['playlist'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canPlaylist') ? true : false);
		$perms['submit'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canSubmit') ? true : false);
		$perms['bypass'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canBypass') ? true : false);
		$perms['mod'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canMod') ? true : false);
		$perms['admin'] = (XenForo_Permission::hasPermission($viewingUser['permissions'], 'EWRmedio', 'canAdmin') ? true : false);

		return $perms;
	}
}