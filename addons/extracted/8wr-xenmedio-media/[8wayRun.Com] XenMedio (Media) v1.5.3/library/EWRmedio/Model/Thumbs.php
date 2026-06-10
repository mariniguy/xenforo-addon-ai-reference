<?php

class EWRmedio_Model_Thumbs extends XenForo_Model
{
	public function buildThumb($mediaID, $thumbURL)
	{
		$targetLoc = XenForo_Helper_File::getExternalDataPath().'/media/'.$mediaID.'.jpg';
		$thumbLoc = XenForo_Helper_File::getTempDir().'/media'.$mediaID;

		if (substr($thumbURL, 0, 7) == 'http://')
		{
			$client = new Zend_Http_Client($thumbURL);
			$response = $client->request();
			$image = $response->getBody();
			file_put_contents($thumbLoc, $image);
		}
		else
		{
			copy($thumbURL, $thumbLoc);
		}

		$imageInfo = getimagesize($thumbLoc);

		if ($image = XenForo_Image_Abstract::createFromFile($thumbLoc, $imageInfo[2]))
		{
			$ratio = 160/90;
			$width = $image->getWidth();
			$height = $image->getHeight();

			if ($width/$height > $ratio)
			{
				$image->thumbnail($width, '90');
			}
			else
			{
				$image->thumbnail('160', $height);
			}

			$width = $image->getWidth();
			$height = $image->getHeight();
			$offWidth = ($width - 160) / 2;
			$offHeight = ($height - 90) / 2;

			$image->crop($offWidth, $offHeight, '160', '90');
			$image->output(IMAGETYPE_JPEG, $targetLoc);
		}

		if (file_exists($thumbLoc)) { unlink($thumbLoc); }
	}

	public function deleteThumb($mediaID)
	{
		$targetLoc = XenForo_Helper_File::getExternalDataPath().'/media/'.$mediaID.'.jpg';
		if (file_exists($targetLoc)) { unlink($targetLoc); }
	}
}