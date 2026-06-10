<?php

class EWRmedio_ControllerPublic_Media_Comment extends XenForo_ControllerPublic_Abstract
{
	public $perms;

	public function actionIndex()
	{
		$cmntID = $this->_input->filterSingle('comment_id', XenForo_Input::UINT);

		if (!$comment = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentByID($cmntID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $comment));
	}

	public function actionEdit()
	{
		if (!$this->perms['admin'] && !$this->perms['mod']) { return $this->responseNoPermission(); }

		$cmntID = $this->_input->filterSingle('comment_id', XenForo_Input::UINT);

		if (!$comment = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentByID($cmntID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_request->isPost())
		{
			$input = array('comment_id' => $comment['comment_id']);
			$input['message'] = $this->_input->filterSingle('message', XenForo_Input::STRING);

			$media = $this->getModelFromCache('EWRmedio_Model_Comments')->updateComment($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
		}

		$viewParams = array(
			'comment' => $comment,
			'media' => $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($comment['media_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_CommentEdit', 'EWRmedio_CommentEdit', $viewParams);
	}

	public function actionDelete()
	{
		if (!$this->perms['admin'] && !$this->perms['mod']) { return $this->responseNoPermission(); }

		$cmntID = $this->_input->filterSingle('comment_id', XenForo_Input::UINT);

		if (!$comment = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentByID($cmntID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_request->isPost())
		{
			$input = array('comment_id' => $comment['comment_id']);
			$input['delete'] = $this->_input->filterSingle('_xfConfirm', XenForo_Input::UINT);

			$media = $this->getModelFromCache('EWRmedio_Model_Comments')->deleteComment($input);
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $media));
		}

		$viewParams = array(
			'comment' => $comment,
			'media' => $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($comment['media_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_CommentDelete', 'EWRmedio_CommentDelete', $viewParams);
	}

	public function actionIp()
	{
		if (!$this->perms['admin'] && !$this->perms['mod']) { return $this->responseNoPermission(); }

		$cmntID = $this->_input->filterSingle('comment_id', XenForo_Input::UINT);

		if (!$comment = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentByID($cmntID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if (!$comment['ip_id'] = $comment['comment_ip'])
		{
			return $this->responseError(new XenForo_Phrase('no_ip_information_available'));
		}

		$viewParams = array(
			'comment' => $comment,
			'media' => $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($comment['media_id']),
			'ipInfo' => $this->getModelFromCache('XenForo_Model_Ip')->getContentIpInfo($comment),
		);

		return $this->responseView('EWRmedio_ViewPublic_CommentIp', 'EWRmedio_CommentIp', $viewParams);
	}

	public function actionReport()
	{
		if (!$this->perms['report']) { return $this->responseNoPermission(); }

		$cmntID = $this->_input->filterSingle('comment_id', XenForo_Input::UINT);

		if (!$comment = $this->getModelFromCache('EWRmedio_Model_Comments')->getCommentByID($cmntID))
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT, XenForo_Link::buildPublicLink('media'));
		}

		if ($this->_request->isPost())
		{
			$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$message)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$this->getModelFromCache('XenForo_Model_Report')->reportContent('media_comment', $comment, $message);

			$controllerResponse = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildPublicLink('media', $comment));
			$controllerResponse->redirectMessage = new XenForo_Phrase('thank_you_for_reporting_this_message');
			return $controllerResponse;
		}

		$viewParams = array(
			'comment' => $comment,
			'media' => $this->getModelFromCache('EWRmedio_Model_Media')->getMediaByID($comment['media_id']),
		);

		return $this->responseView('EWRmedio_ViewPublic_CommentReport', 'EWRmedio_CommentReport', $viewParams);
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
        $output = array();
        foreach ($activities as $key => $activity)
		{
			$output[$key] = new XenForo_Phrase('viewing_media_library');
        }

        return $output;
	}

	protected function _preDispatch($action)
	{
		parent::_preDispatch($action);

		$this->perms = $this->getModelFromCache('EWRmedio_Model_Perms')->getPermissions();

		if (!$this->perms['browse']) { throw $this->getNoPermissionResponseException(); }
	}
}