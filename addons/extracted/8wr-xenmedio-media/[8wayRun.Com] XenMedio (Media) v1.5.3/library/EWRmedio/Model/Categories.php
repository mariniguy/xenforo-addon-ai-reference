<?php

class EWRmedio_Model_Categories extends XenForo_Model
{
	public function getCategoryByID($catID)
	{
		if (!$category = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_categories
			WHERE category_id = ?
		", $catID))
		{
			return false;
		}

        return $category;
	}

	public function getCategoriesByIDs($catIDs)
	{
		if (!$categories = $this->fetchAllKeyed("
			SELECT *
				FROM EWRmedio_categories
			WHERE category_id IN (" . $this->_getDb()->quote($catIDs) . ")
		", 'category_id'))
		{
			return array();
		}

        return $categories;
	}

	public function getCategoryCount()
	{
        $count = $this->_getDb()->fetchRow("
			SELECT COUNT(*) AS total
				FROM EWRmedio_categories
		");

		return $count['total'];
	}

	public function updateCategory($input)
	{
		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Categories');

		if (!empty($input['category_id']) && $category = $this->getCategoryByID($input['category_id']))
		{
			$dw->setExistingData($category);
		}
		$dw->bulkSet(array(
			'category_name' => $input['category_name'],
			'category_description' => XenForo_Helper_String::autoLinkBbCode($input['category_description']),
			'category_parent' => $input['category_parent'],
			'category_disabled' => $input['category_disabled'],
		));
		$dw->save();
		$input['category_id'] = $dw->get('category_id');

		return $input;
	}

	public function deleteCategory($input)
	{
		$this->_getDb()->query("
			UPDATE EWRmedio_media
			SET category_id = ?
			WHERE category_id = ?
		", array($input['category_parent'], $input['category_id']));

		$this->_getDb()->query("
			UPDATE EWRmedio_categories
			SET category_parent = ?
			WHERE category_parent = ?
		", array($input['category_parent'], $input['category_id']));

		$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Categories');
		$dw->setExistingData($input);
		$dw->delete();
	}

	public function updateCategories($input)
	{
		foreach ($input['category_order'] AS $key => $order)
		{
			$this->_getDb()->query("
				UPDATE EWRmedio_categories
				SET category_order = ?
				WHERE category_id = ?
			", array($order, $key));
		}

		return true;
	}
}