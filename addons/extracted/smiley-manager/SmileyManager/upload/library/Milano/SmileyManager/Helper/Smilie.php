<?php

class Milano_SmileyManager_Helper_Smilie
{
	public static function parseSmilies($string)
	{
		$formatter = XenForo_BbCode_Formatter_Base::create('Base');

		return $formatter->replaceSmiliesInText($string);
	}
}