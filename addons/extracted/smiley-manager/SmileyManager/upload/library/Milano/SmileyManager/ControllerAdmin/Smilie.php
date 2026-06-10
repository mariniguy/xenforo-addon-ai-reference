<?php

class Milano_SmileyManager_ControllerAdmin_Smilie extends XFCP_Milano_SmileyManager_ControllerAdmin_Smilie
{
	public function actionImportForm()
	{
		$input = $this->_input->filter(array(
			'mode' => XenForo_Input::STRING,
			'sprite_image' => XenForo_Input::STRING,
			'new' => XenForo_Input::UINT,
			'title' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'smilie_category_id' => XenForo_Input::UINT,
		));

		if ($input['mode'] == 'sprite_image')
		{
			if (!file_exists($input['sprite_image']))
			{
				throw $this->responseException($this->responseError(new XenForo_Phrase('SmileyManager_sprite_not_found'), 404));
			}

			if ($input['new'])
			{
				$keys = array('title', 'display_order');
			}
			else
			{
				$keys = array('smilie_category_id');
			}
			$keys[] = 'new';

			$viewParams = array(
				'spriteImage' => $input['sprite_image'],
				'smilieCategoryOptions' => $this->_getSmilieModel()->getSmilieCategoryOptions(),

				'postData' => XenForo_Application::arrayFilterKeys($input, $keys)
			);

			return $this->responseView('Milano_SmileyManager_ViewAdmin_Smilie_ImportSprite', 'SmileyManager_smilie_import_sprite_image', $viewParams);
		}

		$response = parent::actionImportForm();

		if ($input['new'])
		{
			$id = rand(10, 20) * -1;
			$response->params['newSmilieCategories'] = array(
				$id => array(
					'id' => $id,
					'title' => $input['title'],
					'display_order' => $input['display_order'],
				)
			);

			$response->params['newSmilieCategoryOptions'][$id] = $input['title'];
			$categoryId = $id;
		}
		else
		{
			$categoryId = $input['smilie_category_id'];
		}

		if (!empty($response->params['smilies']))
		{
			foreach ($response->params['smilies'] as &$smilie) 
			{
				$smilie['smilie_category_id'] = $categoryId;
			}
		}

		if ($input['mode'] == 'directory')
		{
			foreach ($response->params['smilies'] as $smilieId => &$smilie) 
			{
				$smilie['smilie_text'] = ':' . strtolower($smilie['title']) . ':';
			}
		}

		return $response;
	}

	public function actionImport()
	{
		$response = parent::actionImport();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$response->params['smilieCategoryOptions'] = $this->_getSmilieModel()->getSmilieCategoryOptions();
		}

		return $response;
	}

	public function actionBatchUpdate()
	{
		if ($this->isConfirmedPost())
		{
			$smilieIds = $this->_input->filterSingle('smilie_ids', XenForo_Input::JSON_ARRAY);
			$categoryId = $this->_input->filterSingle('smilie_category_id', XenForo_Input::UINT);

			if (!empty($smilieIds))
			{
				XenForo_Db::beginTransaction();

				foreach ($smilieIds as $smilieId) 
				{
					$dw = XenForo_DataWriter::create('XenForo_DataWriter_Smilie');
					$dw->setExistingData($smilieId);
					$dw->set('smilie_category_id', $categoryId);
					$dw->save();
				}

				XenForo_Db::commit();
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('smilies')
			);
		}
		else
		{
			$smilieIds = $this->_input->filterSingle('smilieId', XenForo_Input::ARRAY_SIMPLE);
			if (!$smilieIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildAdminLink('smilies')
				);
			}

			return $this->responseView('Milano_SmileyManager_ViewAdmin_Smilie_BatchUpdate', 'SmileyManager_smilie_batch_update', array(
				'smilieIds' => $smilieIds,
				'totalSmilies' => count($smilieIds),
				'smilieCategoryOptions' => $this->_getSmilieModel()->getSmilieCategoryOptions()
			));
		}
	}

	public function actionSprite()
	{
		$this->_assertPostOnly();

		$input = $this->_input->filter(array(
			'sprite_image' => XenForo_Input::STRING,
			'key' => XenForo_Input::UINT,
			'width' => XenForo_Input::UINT,
			'height' => XenForo_Input::UINT,
			'x' => XenForo_Input::STRING,
			'y' => XenForo_Input::STRING,

			'new' => XenForo_Input::UINT,
			'title' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'smilie_category_id' => XenForo_Input::UINT
		));

		$viewParams = array(
			'i' => $input['key'],
			'smilie' => array(
				'image_url' => $input['sprite_image'],
				'sprite_mode' => 1,
				'sprite_params' => array(
					'w' => $input['width'],
					'h' => $input['height'],
					'x' => $input['x'],
					'y' => $input['y']
				),
				'display_order' => $input['key'] * 10,
				'display_in_editor' => 1
			),
			'smilieCategoryOptions' => $this->_getSmilieModel()->getSmilieCategoryOptions(),
		);

		if ($input['new'])
		{
			$id = rand(10, 20) * -1;
			$viewParams['newSmilieCategories'] = array(
				$id => array(
					'id' => $id,
					'title' => $input['title'],
					'display_order' => $input['display_order'],
				)
			);

			$viewParams['newSmilieCategoryOptions'][$id] = $input['title'];
			$categoryId = $id;
		}
		else
		{
			$categoryId = $input['smilie_category_id'];
		}
		$viewParams['smilie']['smilie_category_id'] = $categoryId;

		return $this->responseView('Milano_SmileyManager_ViewAdmin_Template', 'SmileyManager_smilie_import_sprite_image_item', $viewParams);
	}
}