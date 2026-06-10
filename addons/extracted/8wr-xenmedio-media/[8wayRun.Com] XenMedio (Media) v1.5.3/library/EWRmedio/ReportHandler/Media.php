<?php

class EWRmedio_ReportHandler_Media extends XenForo_ReportHandler_Abstract
{
	public function getReportDetailsFromContent(array $content)
	{
		$mediaModel = XenForo_Model::create('EWRmedio_Model_Media');

		return array(
			$content['media_id'],
			$content['user_id'],
			$mediaModel->getMediaByID($content['media_id'])
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		return $reports;
	}

	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('media').': '. $contentInfo['media_title'];
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('media', $contentInfo);
	}

	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		$parser = new XenForo_BbCode_Parser(
			XenForo_BbCode_Formatter_Base::create('Base', array('view' => $view))
		);

		return $view->createTemplateObject('EWRmedio_Report_Media', array(
			'report' => $report,
			'content' => $contentInfo,
			'bbCodeParser' => $parser
		));
	}
}