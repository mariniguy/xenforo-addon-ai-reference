<?php

class Milano_SmileyManager_Listener
{
	public static function loadAccountController($class, array &$extend)
	{
		$extend[] = 'Milano_SmileyManager_ControllerPublic_Account';
	}

	public static function loadSmilieControllerAdmin($class, array &$extend)
	{
		$extend[] = 'Milano_SmileyManager_ControllerAdmin_Smilie';
	}

	public static function loadUserDataWriter($class, array &$extend)
	{
		$extend[] = 'Milano_SmileyManager_DataWriter_User';
	}

    public static function loadBbCodeBase($class, array &$extend)
    {
        //$extend[] = 'Milano_SmileyManager_BbCode_Formatter_Base';
    }

    public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {        
        XenForo_Template_Helper_Core::$helperCallbacks += array(
            'parsesmilies' => array('Milano_SmileyManager_Helper_Smilie', 'parseSmilies'),
        );
    }

	public static function templateEditorCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {        
        if (self::_assertQuickloadSmileyEnabled())
        {	
        	$template->addRequiredExternal('js', 'js/Milano/SmileyManager/editor.js');
        }
    }

    public static function editorSetup(XenForo_View $view, $formCtrlName, &$message, array &$editorOptions, &$showWysiwyg)
    {
        if ($showWysiwyg && self::_assertQuickloadSmileyEnabled())
        {
        	$editorOptions['json']['editorOptions']['plugins'][] = 'SmileyManager';
        }
    }

    protected static function _assertQuickloadSmileyEnabled()
    {
    	if (XenForo_Application::get('options')->SmileyManager_quickloadSmiley)
    	{	
    		$visitor = XenForo_Visitor::getInstance();
    		if (!empty($visitor['quickload_smiley']))
    		{
    			return true;
    		}
    	}

    	return false;
    }
}