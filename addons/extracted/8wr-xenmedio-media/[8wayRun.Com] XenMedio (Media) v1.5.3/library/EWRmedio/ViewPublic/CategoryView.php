<?php

class EWRmedio_ViewPublic_CategoryView extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		$this->_params['category']['HTML'] = new XenForo_BbCode_TextWrapper($this->_params['category']['category_description'], $bbCodeParser);
	}
}