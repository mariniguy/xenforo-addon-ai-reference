<?php

class EWRmedio_ViewPublic_Submit extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'media_description', $this->_params['media']['media_description']
		);
	}
}