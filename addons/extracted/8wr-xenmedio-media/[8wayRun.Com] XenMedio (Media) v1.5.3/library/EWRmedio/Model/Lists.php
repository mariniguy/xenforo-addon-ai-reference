<?php

class EWRmedio_Model_Lists extends XenForo_Model
{
	public function getMediaCount($type = null, $where = 0)
	{
		switch ($type)
		{
			case "category":	$onlyWhere = "WHERE EWRmedio_media.category_id = ".$where;		break;
			case "user":		$onlyWhere = "WHERE EWRmedio_media.user_id = ".$where;			break;
			case "service":		$onlyWhere = "WHERE EWRmedio_media.service_id = ".$where;		break;
			case "keyword":		$onlyWhere = "LEFT JOIN EWRmedio_keylinks ON (EWRmedio_keylinks.media_id = EWRmedio_media.media_id)
									LEFT JOIN EWRmedio_keywords ON (EWRmedio_keywords.keyword_id = EWRmedio_keylinks.keyword_id)
									WHERE EWRmedio_keywords.keyword_id = ".$where;				break;
			default:			$onlyWhere = "WHERE EWRmedio_media.media_state = 'visible'";	break;
		}

        $count = $this->_getDb()->fetchRow("
			SELECT COUNT(*) AS total
				FROM EWRmedio_media
			$onlyWhere
				AND EWRmedio_media.media_state = 'visible'
		");

		return $count['total'];
	}

	public function getMediaList($start, $stop, $sort = 'date', $order = 'DESC', $type = null, $where = null, $local = false)
	{
		switch ($type)
		{
			case "category":	$onlyWhere = "WHERE EWRmedio_media.category_id = ".$where;		break;
			case "user":		$onlyWhere = "WHERE EWRmedio_media.user_id = ".$where;			break;
			case "service":		$onlyWhere = "WHERE EWRmedio_media.service_id = ".$where;		break;
			case "keyword":		$onlyWhere = "LEFT JOIN EWRmedio_keylinks ON (EWRmedio_keylinks.media_id = EWRmedio_media.media_id)
									LEFT JOIN EWRmedio_keywords ON (EWRmedio_keywords.keyword_id = EWRmedio_keylinks.keyword_id)
									WHERE EWRmedio_keywords.keyword_id = ".$where;				break;
			default:			$onlyWhere = "WHERE EWRmedio_media.media_state = 'visible'";	break;
		}

		if ($local)
		{
			$local = "AND EWRmedio_services.service_feed = 'null'";
		}

		$start = ($start - 1) * $stop;
		if ($sort == "popular")
		{
			$orderBy = 'EWRmedio_media.media_comments DESC, EWRmedio_media.media_likes DESC, EWRmedio_media.media_views DESC';
		}
		else
		{
			$orderBy = 'EWRmedio_media.media_'.$sort.' '.$order;
		}

		$medias = $this->_getDb()->fetchAll("
			SELECT EWRmedio_media.*, EWRmedio_categories.*, EWRmedio_services.*, xf_user.*,
				EWRmedio_media.service_value2 AS service_value2,
				IF(NOT ISNULL(xf_user.user_id), xf_user.username, EWRmedio_media.username) AS username
				FROM EWRmedio_media
				LEFT JOIN EWRmedio_categories ON (EWRmedio_categories.category_id = EWRmedio_media.category_id)
				LEFT JOIN EWRmedio_services ON (EWRmedio_services.service_id = EWRmedio_media.service_id)
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_media.user_id)
			$onlyWhere
			$local
				AND EWRmedio_media.media_state = 'visible'
			ORDER BY $orderBy, EWRmedio_media.media_date DESC, EWRmedio_media.media_id DESC
			LIMIT ?, ?
		", array($start, $stop));

		foreach ($medias AS &$media)
		{
			$media = $this->getModelFromCache('EWRmedio_Model_Parser')->parseReplace($media);
			$media = $this->getModelFromCache('EWRmedio_Model_Media')->getDuration($media);
		}

        return $medias;
	}

	public function getCategories()
	{
        $pages = $this->_getDb()->fetchAll("
			SELECT EWRmedio_categories.*, COUNT(EWRmedio_media.media_id) AS count
				FROM EWRmedio_categories
				LEFT JOIN EWRmedio_media ON (EWRmedio_media.category_id = EWRmedio_categories.category_id)
			GROUP BY EWRmedio_categories.category_id
			ORDER BY category_order, category_name ASC
		");

		return $pages;
	}

	public function getCategoryList($parent = 0, &$fullCategoryList = array(), $depth = 0, $categories = false)
	{
		if (!$categories) { $categories = $this->getCategories(); }

		foreach ($categories AS $category)
		{
			if ($category['category_parent'] == $parent)
			{
				$category['category_depth'] = $depth;
				$category['category_indent'] = "";
				for ($counter = 1; $counter <= $depth; $counter++)
				{
					$category['category_indent'] .= "&nbsp; &nbsp; ";
				}
				$fullCategoryList[$category['category_id']] = $category;

				$this->getCategoryList($category['category_id'], $fullCategoryList, $depth+1, $categories);
			}
		}

		return $fullCategoryList;
	}

	public function getUserList()
	{
        $userList = $this->_getDb()->fetchAll("
			SELECT COUNT(EWRmedio_media.media_id) AS count, xf_user.*
				FROM EWRmedio_media
				LEFT JOIN xf_user ON (xf_user.user_id = EWRmedio_media.user_id)
			WHERE NOT ISNULL(xf_user.user_id)
			GROUP BY EWRmedio_media.user_id
			ORDER BY count DESC
			LIMIT ?
		", 10);

		return $userList;
	}

	public function getCrumbs($category, &$breadCrumbs = array())
	{
		$breadCrumbs['cat'.$category['category_id']] = array(
			 'value' => $category['category_name'],
			 'href' => XenForo_Link::buildPublicLink('full:media/category', $category), 
		);

		if ($category['category_parent'])
		{
			$topCategory = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($category['category_parent']);
			$breadCrumbs = $this->getCrumbs($topCategory, $breadCrumbs);
		}

		return $breadCrumbs;
	}
}