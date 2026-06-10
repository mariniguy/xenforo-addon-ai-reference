<?php

class EWRmedio_ViewPublic_PlaylistCreate extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'playlist_description'
		);
	}
}