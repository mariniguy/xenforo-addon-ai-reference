<?php

class EWRmedio_ReportHandler_Comments extends XenForo_ReportHandler_Abstract
{
	public function getReportDetailsFromContent(array $content)
	{
		$commentModel = XenForo_Model::create('EWRmedio_Model_Comments');

		return array(
			$content['comment_id'],
			$content['user_id'],
			$commentModel->getCommentByID($content['comment_id'])
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		return $reports;
	}

	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('comment_on_media_x', array('title' => $contentInfo['media_title']));
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('media', $contentInfo);
	}

	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		return $view->createTemplateObject('EWRmedio_Report_Comment', array(
			'report' => $report,
			'content' => $contentInfo
		));
	}
}