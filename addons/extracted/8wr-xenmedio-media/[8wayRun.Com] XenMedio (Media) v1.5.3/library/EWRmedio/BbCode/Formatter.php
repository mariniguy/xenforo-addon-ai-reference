<?php

class EWRmedio_BbCode_Formatter extends XFCP_EWRmedio_BbCode_Formatter
{
    protected $_tags;

    public function getTags()
    {
        $this->_tags = parent::getTags();

        $this->_tags['medio'] = array(
			'trimLeadingLinesAfter' => 1,
			'callback' => array($this, 'renderTagMedio'),
        );

        return $this->_tags;
    }

	public function renderTagMedio(array $tag, array $rendererStates)
	{
		$text = $this->renderSubTree($tag['children'], $rendererStates);
		$topt = $tag['option'];

		if ($media = XenForo_Model::create('EWRmedio_Model_Media')->getMediaByID($text, false))
		{
			if ($this->_view)
			{
				if ($topt == 'full')
				{
					$bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base'));
					$media['media_description'] = new XenForo_BbCode_TextWrapper($media['media_description'], $bbCodeParser);

					$keywords = explode(",", $media['media_keywords']);
					foreach ($keywords AS &$keyword)
					{
						$keyword = trim($keyword);
						$keyword = '<a href="'.XenForo_Link::buildPublicLink('media/keyword', array('keyword_text' => $keyword)).'">'.$keyword.'</a>';
					}
					$media['media_keywords'] = implode(", ", $keywords);

					$template = $this->_view->createTemplateObject('EWRmedio_BBcode_Full', array('media' => $media));
				}
				else
				{
					$viewParams = array('media' => $media);

					if ($topt == 'left' || $topt == 'right')
					{
						$viewParams['float'] = $topt;
					}

					$template = $this->_view->createTemplateObject('EWRmedio_BBcode', $viewParams);
				}

				return $template->render();
			}

			return '<a href="'.XenForo_Link::buildPublicLink('media', $media).'">' . $media['media_title'] . '</a>';
		}
		else
		{
			return '[medio]'.$text.'[/medio]';
		}
	}
}