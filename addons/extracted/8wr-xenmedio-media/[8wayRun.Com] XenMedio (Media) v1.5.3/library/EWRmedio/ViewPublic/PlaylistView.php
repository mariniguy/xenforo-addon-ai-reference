<?php

class EWRmedio_ViewPublic_PlaylistView extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		$this->_params['playlist']['HTML'] = new XenForo_BbCode_TextWrapper($this->_params['playlist']['playlist_description'], $bbCodeParser);
	}
}