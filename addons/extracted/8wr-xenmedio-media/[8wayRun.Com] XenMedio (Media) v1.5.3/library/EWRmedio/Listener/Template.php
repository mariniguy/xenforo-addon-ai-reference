<?php

class EWRmedio_Listener_Template
{
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$listModel = XenForo_Model::create('EWRmedio_Model_Lists');

		switch ($hookName)
		{
			case 'account_alerts_extra':
			{
				$contents .= $template->create('EWRmedio_AlertPreferences');
				break;
			}
			case 'account_preferences_options':
			{
				$params = $template->getParams();
				$hookParams['media'] = $params['media'];
				
				$contents = $template->create('EWRmedio_AccountPreferences', $hookParams) . $contents;
				break;
			}
			case 'account_wrapper_sidebar_your_account':
			{
				$contents .= $template->create('EWRmedio_Watch_Wrapper');
				break;
			}
			case 'member_view_tabs_heading':
			{
				if ($listModel->getMediaCount('user', $hookParams['user']['user_id']))
				{
					$contents .= $template->create('EWRmedio_Profile_Heading', $hookParams);
				}
				break;
			}
			case 'member_view_tabs_content':
			{
				if ($listModel->getMediaCount('user', $hookParams['user']['user_id']))
				{
					$contents .= $template->create('EWRmedio_Profile_Content', $hookParams);
				}
				break;
			}
			case 'navigation_visitor_tab_links2':
			{
				$contents = str_replace(
					new XenForo_Phrase('watched_threads').'</a></li>',
					new XenForo_Phrase('watched_threads').'</a></li>
						<li><a href="'.new XenForo_Link('watched/media').'">'.new XenForo_Phrase('watched_media').'</a></li>',
					$contents
				);
				break;
			}
			case 'search_form_tabs':
			{
				$contents .= $template->create('EWRmedio_Search_Tab', $template->getParams());
				break;
			}
		}
	}
}