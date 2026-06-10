<?php

class EWRmedio_Model_Custom extends XenForo_Model
{
	public function getCustomValues($media)
	{
		$options = XenForo_Application::get('options');
		$customs = array();
		$cache = unserialize($media['media_custom_cache']);
		
		if (empty($cache))
		{
			if ($options->EWRmedio_custom1_name && $media['media_custom1'])
			{
				$customs[] = $this->arrangeCustomValues(
					$options->EWRmedio_custom1_name,
					$options->EWRmedio_custom1_type,
					$options->EWRmedio_custom1_params,
					$media['media_custom1']
				);
			}
			
			if ($options->EWRmedio_custom2_name && $media['media_custom2'])
			{
				$customs[] = $this->arrangeCustomValues(
					$options->EWRmedio_custom2_name,
					$options->EWRmedio_custom2_type,
					$options->EWRmedio_custom2_params,
					$media['media_custom2']
				);
			}
			
			if ($options->EWRmedio_custom3_name && $media['media_custom3'])
			{
				$customs[] = $this->arrangeCustomValues(
					$options->EWRmedio_custom3_name,
					$options->EWRmedio_custom3_type,
					$options->EWRmedio_custom3_params,
					$media['media_custom3']
				);
			}
			
			if ($options->EWRmedio_custom1_name && $media['media_custom4'])
			{
				$customs[] = $this->arrangeCustomValues(
					$options->EWRmedio_custom4_name,
					$options->EWRmedio_custom4_type,
					$options->EWRmedio_custom4_params,
					$media['media_custom4']
				);
			}
			
			if ($options->EWRmedio_custom5_name && $media['media_custom5'])
			{
				$customs[] = $this->arrangeCustomValues(
					$options->EWRmedio_custom5_name,
					$options->EWRmedio_custom5_type,
					$options->EWRmedio_custom5_params,
					$media['media_custom5']
				);
			}
			
			$dw = XenForo_DataWriter::create('EWRmedio_DataWriter_Media');
			$dw->setExistingData($media);
			$dw->set('media_custom_cache', $customs);
			$dw->save();
		}
		else
		{
			$customs = $cache;
		}
		
		return $customs;
	}
	
	public function arrangeCustomValues($name, $type, $params, $media)
	{
		$custom['name'] = $name;
		
		switch ($type)
		{
			case 'textbox':
			case 'spinbox':
			case 'onoff':
			
				$custom['value'] = $media;
				break;
				
			case 'radio':
			case 'select':
			case 'checkbox':
			
				$matches = explode("\r\n", $params);
				$choices = array();
				
				foreach ($matches AS $match)
				{
					if (preg_match("#([\w-]+)=(.+)#", $match, $subMatches))
					{
						$choices[$subMatches[1]] = $subMatches[2];
					}
				}
				
				if (!empty($choices[$media]))
				{
					$custom['value'] = $choices[$media];
				}

				break;
		}
		
		if ($type == 'checkbox')
		{
			$values = explode(',', $media);
		
			foreach ($values AS $key => &$value)
			{
				if (!empty($choices[$value]))
				{
					$value = $choices[$value];
				}
				else
				{
					unset($values[$key]);
				}
			}
			
			$custom['value'] = implode(', ', $values);
		}
		
		return $custom;
	}

	public function getCustomOptions($media = false)
	{
		$options = XenForo_Application::get('options');
		$customs = array();
		
		if ($options->EWRmedio_custom1_name)
		{
			$customs['custom1'] = $this->arrangeCustomOptions('custom1',
				$options->EWRmedio_custom1_name,
				$options->EWRmedio_custom1_type,
				$options->EWRmedio_custom1_params,
				!empty($media['media_custom1']) ? $media['media_custom1'] : false
			);
		}
		
		if ($options->EWRmedio_custom2_name)
		{
			$customs['custom2'] = $this->arrangeCustomOptions('custom2',
				$options->EWRmedio_custom2_name,
				$options->EWRmedio_custom2_type,
				$options->EWRmedio_custom2_params,
				!empty($media['media_custom2']) ? $media['media_custom2'] : false
			);
		}
		
		if ($options->EWRmedio_custom3_name)
		{
			$customs['custom3'] = $this->arrangeCustomOptions('custom3',
				$options->EWRmedio_custom3_name,
				$options->EWRmedio_custom3_type,
				$options->EWRmedio_custom3_params,
				!empty($media['media_custom3']) ? $media['media_custom3'] : false
			);
		}
		
		if ($options->EWRmedio_custom4_name)
		{
			$customs['custom4'] = $this->arrangeCustomOptions('custom4',
				$options->EWRmedio_custom4_name,
				$options->EWRmedio_custom4_type,
				$options->EWRmedio_custom4_params,
				!empty($media['media_custom4']) ? $media['media_custom4'] : false
			);
		}
		
		if ($options->EWRmedio_custom5_name)
		{
			$customs['custom5'] = $this->arrangeCustomOptions('custom5',
				$options->EWRmedio_custom5_name,
				$options->EWRmedio_custom5_type,
				$options->EWRmedio_custom5_params,
				!empty($media['media_custom5']) ? $media['media_custom5'] : false
			);
		}
		
		return $customs;
	}
	
	public function arrangeCustomOptions($id, $name, $type, $params, $media)
	{
		$custom['name'] = $name;
		$custom['type'] = $type;
		
		switch ($type)
		{
			case 'textbox':
			case 'spinbox':
			case 'onoff':
			
				preg_match_all("#([\w-]+)=(.+)#", $params, $matches);
				
				if(!empty($matches[1]) && !empty($matches[2]))
				{
					$custom['params'] = array_combine($matches[1],$matches[2]);
				}
				
				break;
				
			case 'radio':
			case 'select':
			case 'checkbox':
			
				$matches = explode("\r\n", $params);
				
				foreach ($matches AS $match)
				{
					if (preg_match("#([\w-]+)=(.+)#", $match, $subMatches))
					{
						$custom['params'][] = array(
							'id' => $subMatches[1],
							'val' => $subMatches[2]
						);
					}
				}
			
				break;
		}
		
		if ($media)
		{
			$custom['value'] = $media;
		
			if ($type == 'checkbox')
			{
				$values = explode(',', $media);
			
				foreach ($custom['params'] AS &$param)
				{
					if (in_array($param['id'], $values))
					{
						$param['checked'] = true;
					}
				}
			}
		}
		
		return $custom;
	}
}