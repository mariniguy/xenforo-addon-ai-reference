<?php

class Milano_SmileyManager_Install extends Milano_Common_Install
{
	/* Start auto-generated lines of code. */

	protected static function _getTables()
	{
		return array();
	}

	protected static function _getTablePatches()
	{
		return array('xf_user_option' => array('quickload_smiley' => 'TINYINT(3) UNSIGNED DEFAULT \'0\''));
	}

	/* End auto-generated lines of code. */

	protected static function _preInstall()
	{
		self::checkXfVersion(1030031, '1.3.0');

		self::_import();
	}

	protected static function _postInstall()
	{
		self::_deleteSimpleCacheData();
		self::_deleteDataRegistry();

		$tablePatches = array('smilie_display_order' => 'INT(10) UNSIGNED DEFAULT \'0\'');
		if (self::isFieldExists('xf_smilie', 'smilie_category_id_old'))
		{
			$tablePatches['smilie_category_id_old'] = 'INT(10) UNSIGNED DEFAULT \'0\'';
		}

		self::dropTablePatches(array('xf_smilie' => $tablePatches));
	}

	protected static function _import()
	{
		$categories = XenForo_Application::getSimpleCacheData('smilieCategories');
		$smilies = XenForo_Application::getSimpleCacheData('groupedSmilies');

		if ($categories)
		{
			foreach ($categories as $category) 
			{
				$dw = XenForo_DataWriter::create('XenForo_DataWriter_SmilieCategory');

				$dw->set('display_order', $category['display_order']);
				$dw->setExtraData(XenForo_DataWriter_SmilieCategory::DATA_TITLE, $category['category_title']);
				$dw->save();

				$newCategory = $dw->getMergedData();
				if (self::isFieldExists('xf_smilie', 'smilie_category_id_old'))
				{
					self::_getDb()->update('xf_smilie', array('smilie_category_id' => $newCategory['smilie_category_id']), 
						'smilie_category_id_old = ' . $category['smilie_category_id']);
				}
				elseif (!empty($smilies[$category['smilie_category_id']]))
				{
					$smilieIds = array_keys($smilies[$category['smilie_category_id']]);
					
					self::_getDb()->update('xf_smilie', array('smilie_category_id' => $newCategory['smilie_category_id']), 
						'smilie_id IN (' . self::_getDb()->quote($smilieIds) . ')');
				}
			}
		}
	}

	protected static function _deleteSimpleCacheData()
	{
		if (XenForo_Application::getSimpleCacheData('groupedSmilies'))
		{
			XenForo_Application::setSimpleCacheData('groupedSmilies', false);
		}
		
		if (XenForo_Application::getSimpleCacheData('smilieCategories'))
		{
			XenForo_Application::setSimpleCacheData('smilieCategories', false);
		}
	}

	protected static function _deleteDataRegistry()
	{
		if (XenForo_Model::create('XenForo_Model_DataRegistry')->get('groupedSmilies'))
		{
			XenForo_Model::create('XenForo_Model_DataRegistry')->delete('groupedSmilies');
		}
	}
}