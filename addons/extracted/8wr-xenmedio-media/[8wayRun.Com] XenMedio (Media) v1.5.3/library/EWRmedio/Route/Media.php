<?php

class EWRmedio_Route_Media implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$components = explode('/', $routePath);
		$subPrefix = strtolower(array_shift($components));
		$subSplits = explode('.', $subPrefix);

		$controllerName = '';
		$action = '';
		$intParams = 'media_id';
		$strParams = '';
		$slice = false;

		switch ($subPrefix)
		{
			case 'comment':		$controllerName = '_Comment';	$intParams = 'comment_id';		$slice = true;	break;
			case 'playlist':	$controllerName = '_Playlist';	$intParams = 'playlist_id';		$slice = true;	break;
			case 'category':	$controllerName = '_Category';	$intParams = 'category_id';		$slice = true;	break;
			case 'user':		$controllerName = '_User';		$intParams = 'user_id';			$slice = true;	break;
			case 'keyword':		$controllerName = '_Keyword';	$strParams = 'keyword_text';	$slice = true;	break;
			case 'service':		$controllerName = '_Service';	$strParams = 'service_slug';	$slice = true;	break;
			case 'admin':		$controllerName = '_Admin';										$slice = true;	break;
			default : 			
				if (is_numeric(end($subSplits))) { $controllerName = '_Media'; }
		}

		if (!empty($components[1]) && is_numeric($components[1])) { unset($components[1]); }
		$routePathAction = ($slice ? implode('/', array_slice($components, 0, 2)) : $routePath);

		if ($strParams)
		{
			$action = $router->resolveActionWithStringParam($routePathAction, $request, $strParams);
		}
		else
		{
			$action = $router->resolveActionWithIntegerParam($routePathAction, $request, $intParams);
		}

		$action = $router->resolveActionAsPageNumber($action, $request);
		$action = (int) $action > 0 ? '' : $action;

		return $router->getRouteMatch('EWRmedio_ControllerPublic_Media'.$controllerName, $action, 'media', $routePath);
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		$components = explode('/', $action);
		$subPrefix = strtolower(array_shift($components));

		$intParams = '';
		$strParams = '';
		$title = '';
		$slice = false;

		switch ($subPrefix)
		{
			case 'comment':		$intParams = 'comment_id';									$slice = true;	break;
			case 'playlist':	$intParams = 'playlist_id';		$title = 'playlist_name';	$slice = true;	break;
			case 'category':	$intParams = 'category_id';		$title = 'category_name';	$slice = true;	break;
			case 'user':		$intParams = 'user_id';			$title = 'username';		$slice = true;	break;
			case 'keyword':		$strParams = 'keyword_text';								$slice = true;	break;
			case 'service':		$strParams = 'service_slug';								$slice = true;	break;
			case 'admin':																	$slice = true;	break;
			default:			$intParams = 'media_id';		$title = 'media_title';
		}

		if ($slice)
		{
			$outputPrefix .= '/'.$subPrefix;
			$action = implode('/', $components);
		}

		$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

		if ($strParams)
		{
			return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, $strParams);
		}
		else
		{
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, $intParams, $title);
		}
	}
}