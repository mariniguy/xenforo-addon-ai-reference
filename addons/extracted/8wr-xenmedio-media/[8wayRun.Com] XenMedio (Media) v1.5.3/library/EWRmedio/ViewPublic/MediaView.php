<?php

class EWRmedio_ViewPublic_MediaView extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		$this->_params['media']['HTML'] = new XenForo_BbCode_TextWrapper($this->_params['media']['media_description'], $bbCodeParser);

		$bbCodeStripper = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Text'));
		$this->_params['media']['TEXT'] = $bbCodeStripper->render($this->_params['media']['media_description']);
	}
}