<?php

class EWRmedio_Model_Keylinks extends XenForo_Model
{
	public function getKeylinkByID($linkID)
	{
		if (!$keylink = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_keylinks
			WHERE keylink_id = ?
		", $linkID))
		{
			return false;
		}

        return $keylink;
	}

	public function getKeylinkByKeywordAndMedia($keywordID, $mediaID)
	{
		if (!$keylink = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_keylinks
			WHERE keyword_id = ? AND media_id = ?
		", array($keywordID, $mediaID)))
		{
			return false;
		}

        return $keylink;
	}

	public function updateKeylinks($newkeys, $media)
	{
		foreach ($newkeys AS $keywordID)
		{
			if (!$link = $this->getKeylinkByKeywordAndMedia($keywordID, $media['media_id']))
			{
				$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Keylinks');
				$dw->set('keyword_id', $keywordID);
				$dw->set('media_id', $media['media_id']);
				$dw->save();
			}
		}

		return true;
	}

	public function deleteKeylinks($oldlinks, $keylinks)
	{
		foreach ($oldlinks AS $key => $value)
		{
			if (!array_key_exists($key, $keylinks))
			{
				$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Keylinks');
				$dw->setExistingData(array('keylink_id' => $key));
				$dw->delete();
			}
		}

		return true;
	}
}