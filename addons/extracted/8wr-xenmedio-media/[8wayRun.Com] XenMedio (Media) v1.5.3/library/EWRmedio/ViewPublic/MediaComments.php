<?php

class EWRmedio_ViewPublic_MediaComments extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$output = $this->_renderer->getDefaultOutputArray(get_class($this), $this->_params, $this->_templateName);
		
		$output['_redirectMessage'] = new XenForo_Phrase('your_comment_has_been_posted');
		
		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}