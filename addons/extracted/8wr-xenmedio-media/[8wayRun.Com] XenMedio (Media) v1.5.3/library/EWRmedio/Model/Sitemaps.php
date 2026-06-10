<?php

class EWRmedio_Model_Sitemaps extends XenForo_Model
{
	public function getRSSbyMedia($media = null, $type = null, $where = null, $local = false)
	{
		$title = htmlspecialchars(XenForo_Application::get('options')->boardTitle.' - '.new XenForo_Phrase('media_library'));
		$link = XenForo_Link::buildPublicLink('full:media');
		$description = htmlspecialchars(XenForo_Application::get('options')->boardDescription);

		if ($type == 'category' && $category = $this->getModelFromCache('EWRmedio_Model_Categories')->getCategoryByID($where))
		{
			$title .= htmlspecialchars(' - '.$category['category_name']);
			$link = XenForo_Link::buildPublicLink('full:media/category', $category);
			$description = htmlspecialchars(XenForo_Helper_String::bbCodeStrip(str_replace("\n", " ", $category['category_description'])));
		}

		if ($type == 'user' && $user = $this->getModelFromCache('XenForo_Model_User')->getUserById($where))
		{
			$title .= htmlspecialchars(' - '.new XenForo_Phrase('user').': '.$user['username']);
			$link = XenForo_Link::buildPublicLink('full:media/user', $user);
		}

		if ($type == 'service' && $service = $this->getModelFromCache('EWRmedio_Model_Services')->getServiceByID($where))
		{
			$title .= htmlspecialchars(' - '.new XenForo_Phrase('service').': '.$service['service_slug']);
			$link = XenForo_Link::buildPublicLink('full:media/service', $service);
		}

		if ($type == 'keyword' && $keyword = $this->getModelFromCache('EWRmedio_Model_Keywords')->getKeywordByID($where))
		{
			$title .= htmlspecialchars(' - '.new XenForo_Phrase('media_keyword').': '.$keyword['keyword_text']);
			$link = XenForo_Link::buildPublicLink('full:media/keyword', $keyword);
		}

		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;

		$document->appendChild($rss_node = $document->createElement('rss'));
			$rss_node->appendChild($ver_node = $document->createAttribute('version'));
				$ver_node->appendChild($document->createTextNode('2.0'));
			$rss_node->appendChild($atm_node = $document->createAttribute('xmlns:atom'));
				$atm_node->appendChild($document->createTextNode('http://www.w3.org/2005/Atom'));
			$rss_node->appendChild($dub_node = $document->createAttribute('xmlns:dc'));
				$dub_node->appendChild($document->createTextNode('http://purl.org/dc/elements/1.1/'));

			$rss_node->appendChild($chn_node = $document->createElement('channel'));

				$chn_node->appendChild($lnk_node = $document->createElement('atom:link'));
					$lnk_node->appendChild($hrf_node = $document->createAttribute('href'));
						$hrf_node->appendChild($document->createTextNode($link.($local ? 'podcast' : 'rss')));
					$lnk_node->appendChild($rel_node = $document->createAttribute('rel'));
						$rel_node->appendChild($document->createTextNode('self'));
					$lnk_node->appendChild($typ_node = $document->createAttribute('type'));
						$typ_node->appendChild($document->createTextNode('application/rss+xml'));

				$chn_node->appendChild($document->createElement('title', $title));
				$chn_node->appendChild($document->createElement('link', $link));
				$chn_node->appendChild($document->createElement('description', $description));

				$chn_node->appendChild($img_node = $document->createElement('image'));
					$img_node->appendChild($document->createElement('title', $title));
					$img_node->appendChild($document->createElement('link', $link));
					$img_node->appendChild($document->createElement('url', XenForo_Application::get('options')->boardUrl.'/styles/default/xenforo/logo.og.png'));

				if ($media)
				{
					$chn_node->appendChild($this->buildRSSbyMedia($document, $media));
				}
				else
				{
					$medias = $this->getModelFromCache('EWRmedio_Model_Lists')->getMediaList(1, 20, 'date', 'DESC', $type, $where, $local);

					foreach ($medias AS $media)
					{
						$chn_node->appendChild($this->buildRSSbyMedia($document, $media, $local));
					}
				}

		return $document;
	}

	public function buildRSSbyMedia($document, $media, $local = false)
	{
		$itm_node = $document->createElement('item');
			$itm_node->appendChild($document->createElement('pubDate', date("r", $media['media_date'])));
			$itm_node->appendChild($document->createElement('title', substr(htmlspecialchars($media['media_title']), 0, 100)));
			$itm_node->appendChild($document->createElement('description', substr(htmlspecialchars(XenForo_Helper_String::bbCodeStrip(str_replace("\n", " ", $media['media_description']))), 0, 2048)));
			$itm_node->appendChild($document->createElement('link', XenForo_Link::buildPublicLink('full:media', $media)));
			$itm_node->appendChild($document->createElement('guid', XenForo_Link::buildPublicLink('full:media', $media)));
			$itm_node->appendChild($document->createElement('comments', XenForo_Link::buildPublicLink('full:media/comments', $media)));

			$itm_node->appendChild($cat_node = $document->createElement('category', htmlspecialchars('+'.$media['category_name'])));
				$cat_node->appendChild($dom_node = $document->createAttribute('domain'));
					$dom_node->appendChild($document->createTextNode(XenForo_Link::buildPublicLink('full:media/category', $media)));
			$itm_node->appendChild($usr_node = $document->createElement('category', htmlspecialchars('+'.$media['username'])));
				$usr_node->appendChild($dom_node = $document->createAttribute('domain'));
					$dom_node->appendChild($document->createTextNode(XenForo_Link::buildPublicLink('full:media/user', $media)));

			if (trim($media['media_keywords']))
			{
				foreach (explode(',', $media['media_keywords']) AS $tag)
				{
					$itm_node->appendChild($tag_node = $document->createElement('category', htmlspecialchars(trim($tag))));
						$tag_node->appendChild($dom_node = $document->createAttribute('domain'));
							$dom_node->appendChild($document->createTextNode(XenForo_Link::buildPublicLink('full:media/keyword', array('keyword_text' => trim($tag)))));
				}
			}

			if ($local && $media['service_feed'] == 'null')
			{
				$itm_node->appendChild($thu_node = $document->createElement('enclosure'));
					$thu_node->appendChild($url_node = $document->createAttribute('url'));
						$url_node->appendChild($document->createTextNode($media['content_loc']));
					$thu_node->appendChild($len_node = $document->createAttribute('length'));
						$len_node->appendChild($document->createTextNode(intval(@filesize(XenForo_Helper_File::getExternalDataPath().'/local/'.$media['service_value']))));
			}
			else
			{
				$itm_node->appendChild($thu_node = $document->createElement('enclosure'));
					$thu_node->appendChild($url_node = $document->createAttribute('url'));
						$url_node->appendChild($document->createTextNode(XenForo_Application::get('options')->boardUrl.'/data/media/'.$media['media_id'].'.jpg'));
					$thu_node->appendChild($len_node = $document->createAttribute('length'));
						$len_node->appendChild($document->createTextNode(intval(@filesize(XenForo_Helper_File::getExternalDataPath().'/media/'.$media['thumbnail']))));
					$thu_node->appendChild($typ_node = $document->createAttribute('type'));
						$typ_node->appendChild($document->createTextNode('image/jpeg'));
			}

			$itm_node->appendChild($document->createElement('dc:creator', htmlspecialchars($media['username'])));

		return $itm_node;
	}
}