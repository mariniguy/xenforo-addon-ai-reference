<?php

class EWRmedio_ViewPublic_ServiceEdit extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'service_parameters', $this->_params['service']['service_parameters'], array('disable' => true)
		);
	}
}