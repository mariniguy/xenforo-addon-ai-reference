<?php

class EWRmedio_ViewPublic_CategoryCreate extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'category_description'
		);
	}
}