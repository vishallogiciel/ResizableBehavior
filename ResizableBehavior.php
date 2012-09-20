<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

class ResizableBehavior extends AttachableBehavior {

	private $_defaults = array(
		'attachments' => array(
			'file' => array(
				'dir' => 'files',						// location of the folder in webroot folder, can specify subfolders also
				'subDir' => 'original',
				'resize' => array(
					'thumbnail' => array(
						'dimension' => '200x160',
						'dimensionType' => 'exact',
						'subDir' => 'thumbnails',
					),
					'gallery' => array(
						'dimension' => '400x320',
						'dimensionType' => 'exact',
						'subDir' => 'gallery',
					)
				)
			),
		),
		'baseDir' => 'uploads',	// relative to webroot
		'dir' => 'files',
		'types' => '*',
		'extensions' => '*',
		'maxSize' => 5242880,
		'physicalName' => '{ID}-{FILENAME}',
		'errorMessages' => array(
			'DIRECTORY_NOT_WRITABLE' => 'Directory not writable.',
			'DIRECTORY_DOES_NOT_EXIST' => "The target directory doesn't exist",
			'INVALID_FILE_TYPE' => 'This file type is not supported.',
			'INVALID_FILE_EXTENSION' => 'This file type is not supported.',
			'INVALID_FILE_SIZE' => 'The file is too large to upload.',
			'ERROR_UPLOADING_FILE' => 'There was an error uploading the file.',
			'FILE_NOT_UPLOADED' => 'The file was not properly uploaded.',
			'PARENT_DIRECTORY_NOT_WRITABLE' => 'The parent directory is now writable.',
		),
		'createDir' => false,
	);

/**
 * setup method
 *
 * @param Model $Model
 * @param array $options
 * @return void
 */
	public function setup(Model $Model, $options = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, $options);
	}

/**
 * beforeSave callback override
 *
 * @param Model $Model
 * @return void
 */
	public function beforeSave(Model $Model) {
		// get all the attachment fields
		$attachments = $this->settings[$Model->alias]['attachments'];
		foreach ($attachments as $label => $options) {
			if (isset($Model->data[$Model->alias][$label])) {
				$attachment = $Model->data[$Model->alias][$label];
				if (!empty($attachment['tmp_name']) && empty($Model->validationErrors)) {
					$uploadedFilePath = $this->uploadAttachment($Model, $label, $attachment, $options);
					if (!empty($uploadedFilePath)) {
						$this->resizeAttachment($Model, $label, $attachment, $options);
					}
				} else {
					unset($Model->data[$Model->alias][$label]);
				}
			}
		}
		if (!empty($Model->validationErrors)) {
			$this->removeUploadedImages($Model, $attachments);
			return false;
		}
		return true;
	}

/**
 * resizeAttachment method
 *
 * @param Model $Model
 * @param string $label
 * @return void
 */
	public function resizeAttachment(Model $Model, $label = '', $attachment = array(), $options = array()) {
		debug($Model);
		exit;
	}

}



/**
 * Resize
 * Class for managing all kind of resizing options for images
 * provide multiple options for resizing ie 'exaxt', 'crop', 'portrait' etc 
 *
 * @package       cake application
 * @subpackage    app.models.behaviors
 * 
 */


private class Resize{
	
	/**
	 * orignal image
	 * @var Object Image
	 */
	private $image;
	/**
	 * Orignal Height of the image
	 * @var int
	 */
	private $height;
	
	/**
	 * Orignal Width of the image
	 * @var int
	 */
	private $width;
	
	/**
	 * Resized Image 
	 * @var Image Object
	 */
	private $resizedImg;

	/**
	 * Image Info array
	 * @var array
	 */
	public $imageInfo;
	
	function __construct($filename){
		// open image
		$this->image = $this->openImage($filename);
		$this->width = imagesx($this->image);
		$this->height = imagesy($this->image);
	}
	
	private function openImage($file) {
		// Image info
		$this->imageInfo = getimagesize($file);
		
		switch ($this->imageInf [2]) {
			case IMAGETYPE_PNG :
				$img = imagecreatefrompng ( $this->image );
			case IMAGETYPE_JPEG :
				$img = imagecreatefromjpeg ( $this->image );
			case IMAGETYPE_GIF :
				$img = imagecreatefromgif ( $this->image );
			default:
				$img = false;
		}
		
		return $img;
	}

	public function resizeImage($newWidth, $newHeight, $mode = 'auto'){
		// Get the optimal width and height based on $mode
		$dimension = $this->getDimensions($newWidth, $newHeight, strtolower($mode));
		$optimalWidth = $dimension['optimalWidth'];
		$optimalHeight = $dimension['optimalHeight'];

		//Create Image canvas of x, y size
		$this->resizedImg = imagecreatetruecolor($optimalWidth, $optimalHeight);
		imagecopyresampled($this->resizedImg, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);
		
		// *** if option is 'crop', then crop too  
    	if ($mode == 'crop') {  
	        $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);  
	    }  
	}
	
	private function getDimensions($newWidth, $newHeight, $mode){
		switch($mode){
			case 'exact':  
	            $optimalWidth = $newWidth;  
	            $optimalHeight= $newHeight;  
	            break;  
	        case 'portrait':  
	            $optimalWidth = $this->getSizeByFixedHeight($newHeight);  
	            $optimalHeight= $newHeight;  
	            break;  
	        case 'landscape':  
	            $optimalWidth = $newWidth;  
	            $optimalHeight= $this->getSizeByFixedWidth($newWidth);  
	            break;  
	        case 'auto':  
	            $optionArray = $this->getSizeByAuto($newWidth, $newHeight);  
	            $optimalWidth = $optionArray['optimalWidth'];  
	            $optimalHeight = $optionArray['optimalHeight'];  
	            break;  
	        case 'crop':  
	            $optionArray = $this->getOptimalCrop($newWidth, $newHeight);  
	            $optimalWidth = $optionArray['optimalWidth'];  
	            $optimalHeight = $optionArray['optimalHeight'];  
	            break;  
		}
		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight); 
	}
	
	
	private function getSizeByFixedHeight($newHeight)
	{
	    $ratio = $this->width / $this->height;
	    $newWidth = $newHeight * $ratio;
	    return $newWidth;
	}

	private function getSizeByFixedWidth($newWidth)
	{
	    $ratio = $this->height / $this->width;
	    $newHeight = $newWidth * $ratio;
	    return $newHeight;
	}

	private function getSizeByAuto($newWidth, $newHeight)
	{
	    if ($this->height < $this->width)
	    // *** Image to be resized is wider (landscape)
	    {
	        $optimalWidth = $newWidth;
	        $optimalHeight= $this->getSizeByFixedWidth($newWidth);
	    }
	    elseif ($this->height > $this->width)
	    // *** Image to be resized is taller (portrait)
	    {
	        $optimalWidth = $this->getSizeByFixedHeight($newHeight);
	        $optimalHeight= $newHeight;
	    }
		else
	    // *** Image to be resizerd is a square
	    {
			if ($newHeight < $newWidth) {
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
			} else if ($newHeight > $newWidth) {
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
			    $optimalHeight= $newHeight;
			} else {
				// *** Sqaure being resized to a square
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
			}
	    }

		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	private function getOptimalCrop($newWidth, $newHeight)
	{

		$heightRatio = $this->height / $newHeight;
		$widthRatio  = $this->width /  $newWidth;

		if ($heightRatio < $widthRatio) {
			$optimalRatio = $heightRatio;
		} else {
			$optimalRatio = $widthRatio;
		}

		$optimalHeight = $this->height / $optimalRatio;
		$optimalWidth  = $this->width  / $optimalRatio;

		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}
	
	private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
	{
	    // *** Find center - this will be used for the crop
	    $cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
	    $cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );
	  
	    $crop = $this->imageResized;  
	    //imagedestroy($this->imageResized);  
	  
	    // *** Now crop from center to exact requested size  
	    $this->imageResized = imagecreatetruecolor($newWidth , $newHeight);  
	    imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);  
	}  
	
	
	public function saveImage($savePath, $imageQuality="100")  {
  		  
	    switch($this->imageInfo[2])  
	    {  
	    	case IMAGETYPE_JPEG:
	                imagejpeg($this->imageResized, $savePath, $imageQuality);
	            break;  
	    	case IMAGETYPE_GIF:  
	                imagegif($this->imageResized, $savePath);
	            break;  
	    	case IMAGETYPE_PNG:  
	            // *** Scale quality from 0-100 to 0-9  
	            $scaleQuality = round(($imageQuality/100) * 9);
	
	            // *** Invert quality setting as 0 is best, not 9
	            $invertScaleQuality = 9 - $scaleQuality;
                imagepng($this->imageResized, $savePath, $invertScaleQuality);
  	          break;  
	  
	        default:  
	            break;  
	    }  
  
    	imagedestroy($this->imageResized);  
	}  
}