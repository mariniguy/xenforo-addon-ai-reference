<?php

class Milano_SmileyManager_ViewAdmin_Smilie_ImportSprite extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$data = $this->_params['postData'];
		$this->_params['hiddenHtml'] = $this->_arrayToHiddenInput($data);
	}

	protected function _arrayToHiddenInput(array $array, $prefix = '')
	{
		$output = '';
		foreach ($array AS $k => $v)
		{
			$name = strlen($prefix) ? $prefix . '[' . $k . ']' : $k;

			if (is_array($v))
			{
				$output .= $this->_arrayToHiddenInput($v, $name);
			}
			else
			{
				$output .= '<input type="hidden" name="' . htmlspecialchars($name, ENT_COMPAT, 'utf-8')
					. '" value="' . htmlspecialchars($v, ENT_COMPAT, 'utf-8') . '" />' . "\n";
			}
		}

		return $output;
	}
}