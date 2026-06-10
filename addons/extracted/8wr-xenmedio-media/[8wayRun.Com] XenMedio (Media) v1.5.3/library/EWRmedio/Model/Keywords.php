<?php

class EWRmedio_Model_Keywords extends XenForo_Model
{
	public function getKeywords($start, $stop)
	{
		$start = ($start - 1) * $stop;

		$keywords = $this->_getDb()->fetchAll("
			SELECT EWRmedio_keywords.*, COUNT(EWRmedio_keylinks.keylink_id) AS count
				FROM EWRmedio_keywords
				LEFT JOIN EWRmedio_keylinks ON (EWRmedio_keylinks.keyword_id = EWRmedio_keywords.keyword_id)
			GROUP BY EWRmedio_keywords.keyword_id
			ORDER BY keyword_text ASC
			LIMIT ?, ?
		", array($start, $stop));

		return $keywords;
	}
	
	public function getAllKeywords()
	{
		$keywords = $this->_getDb()->fetchAll("SELECT * FROM EWRmedio_keywords ORDER BY keyword_text ASC");

		return $keywords;
	}

	public function getKeywordCount()
	{
		$count = $this->_getDb()->fetchRow("
			SELECT COUNT(*) AS total
				FROM EWRmedio_keywords
		");

		return $count['total'];
	}

	public function getKeywordByText($text)
	{
		if (!$keyword = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_keywords
			WHERE keyword_text = ?
		", $text))
		{
			return false;
		}

        return $keyword;
	}

	public function getKeywordByID($wordID)
	{
		if (!$keyword = $this->_getDb()->fetchRow("
			SELECT *
				FROM EWRmedio_keywords
			WHERE keyword_id = ?
		", $wordID))
		{
			return false;
		}

        return $keyword;
	}

	public function getKeywordCloud($limit, $min_size, $max_size)
	{
        if (!$keywords = $this->_getDb()->fetchAll("
			SELECT COUNT(*) AS count, EWRmedio_keywords.* 
				FROM EWRmedio_keylinks
				LEFT JOIN EWRmedio_keywords ON (EWRmedio_keywords.keyword_id = EWRmedio_keylinks.keyword_id)
			GROUP BY EWRmedio_keywords.keyword_id
			ORDER BY count DESC
			LIMIT ?
		", $limit))
		{
			return false;
		}

		$tags = array();
		$text = array();

		foreach ($keywords AS $keyword)
		{
			$tags[$keyword['keyword_id']] = $keyword['count'];
			$text[$keyword['keyword_id']] = $keyword['keyword_text'];
		}

		$max_qty = max(array_values($tags));
		$min_qty = min(array_values($tags));
		
		$spread = $max_qty - $min_qty;
		$spread = $spread ? $spread : 1;

		$step = ($max_size - $min_size) / ($spread);

		asort($text);
		$keywords = array();
	
		foreach ($text AS $keyword_id => $keyword_text)
		{
			$keywords[$keyword_id]['keyword_id'] = $keyword_id;
			$keywords[$keyword_id]['keyword_text'] = $keyword_text;
			$keywords[$keyword_id]['keyword_size'] = floor($min_size + (($tags[$keyword_id] - $min_qty) * $step));
			$keywords[$keyword_id]['keyword_count'] = $tags[$keyword_id];
		}

		return $keywords;
	}

	public function getAnimatedCloud($keyArray)
	{
		$keywords = "";

		foreach ($keyArray AS $keyword)
		{
			$keyword['keyword_link'] = XenForo_Link::buildPublicLink('media/keyword', $keyword);
			$keywords .= "<a href='".$keyword['keyword_link']."' style='font-size: ".$keyword['keyword_size']."px;'>".$keyword['keyword_text']."</a>";
		}

		return $keywords;
	}

	public function updateKeywords($keywords)
	{
		$options = XenForo_Application::get('options');
		$newkeys = array();

		$keywords = strtolower($keywords);
		$keywords = preg_replace('#[^a-z0-9\-\s\,]#', '-', $keywords);
		$keywords = preg_replace('#^[\-\s\,]+#', '', $keywords);
		$keywords = preg_replace('#\s*\-+\s*#', '-', $keywords);
		$keywords = preg_replace('#\-+#', '-', $keywords);
		$keywords = preg_replace('#\s+#', ' ', $keywords);
		$keywords = preg_replace('#[\-\s\,]?,[\-\s\,]*#', ',', $keywords);
		$keywords = explode(",", $keywords);
		$keywords = array_map("trim", $keywords);

		foreach ($keywords AS $keyword)
		{
			if (strlen($keyword) < $options->EWRmedio_minkeyword) { continue; }
			if (strlen($keyword) > $options->EWRmedio_maxkeyword)
			{
				$keyword = substr($keyword, 0, $options->EWRmedio_maxkeyword);
			}

			if (!$word = $this->getKeywordByText($keyword))
			{
				$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Keywords');
				$dw->set('keyword_text', $keyword);
				$dw->save();
				$word['keyword_id'] = $dw->get('keyword_id');
			}

			$newkeys[] = $word['keyword_id'];
		}

		return $newkeys;
	}

	public function deleteKeywords($keywords)
	{
		$keywords = implode(",", $keywords);

		if ($keywords)
		{
			$medias = $this->_getDb()->fetchAll("
				SELECT EWRmedio_media.*
					FROM EWRmedio_media
					LEFT JOIN EWRmedio_keylinks ON (EWRmedio_keylinks.media_id = EWRmedio_media.media_id)
				WHERE EWRmedio_keylinks.keyword_id IN ($keywords)
			");

			$this->_getDb()->query("
				DELETE FROM EWRmedio_keylinks
				WHERE keyword_id IN ($keywords)
			");

			$this->_getDb()->query("
				DELETE FROM EWRmedio_keywords
				WHERE keyword_id IN ($keywords)
			");

			foreach ($medias AS $media)
			{
				$media['media_keywords'] = $this->getModelFromCache('EWRmedio_Model_Media')->updateKeywords($media);
			}
		}
	}
}