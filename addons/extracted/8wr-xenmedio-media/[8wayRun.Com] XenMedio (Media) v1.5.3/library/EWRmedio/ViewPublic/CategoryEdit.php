<?php

class EWRmedio_ViewPublic_CategoryEdit extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'category_description', $this->_params['category']['category_description']
		);
	}
}