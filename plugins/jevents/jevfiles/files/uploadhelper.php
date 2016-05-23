<?php
/**
 * @version 0.0.1
 * @package JEV Files Plugin 
 * @copyright (C) 2009 GWE Systems Ltd
 * @license by negotiation
 */
if (!defined("_JEXEC"))
	die("sorry");

class JevUploadHelper
{

	private $params;

	function __construct($params)
	{
		$this->params = $params;

	}

	function processFileUpload($file, $suffix="xml", $allowedfileExtensions = array("flv", "xml"))
	{
		set_time_limit(1800);
		if (!array_key_exists("HTTP_REFERER", $_SERVER) || (strpos($_SERVER["HTTP_REFERER"], "http://" . $_SERVER["HTTP_HOST"]) !== 0 && strpos($_SERVER["HTTP_REFERER"], "https://" . $_SERVER["HTTP_HOST"]) !== 0))
		{
			die();
		}

		$files = JFactory::getApplication()->input->files;
		$uploadedFile = $files->get($file, false);
		if (!isset($uploadedFile) || $uploadedFile['error'] == UPLOAD_ERR_NO_FILE)
		{
			$this->error_goback(JText::_("JEV_Missing_File"));
		}

		// this should be set in config
		$filesize = $uploadedFile["size"];
		$maxsize = $this->params->get("maxuploadfile", 2000000);
		if ($uploadedFile["size"] > $maxsize)
		{
			$maxsize = $this->human_filesize($maxsize, 2);
			$filesize = $this->human_filesize($filesize, 2);
			$this->error_goback(JText::sprintf("JEV_File_Too_Large", $filesize, $maxsize));
		}

		$this->securityCheck($uploadedFile);

		if (!$this->checkFileType($uploadedFile["type"], $uploadedFile["name"], $suffix, $allowedfileExtensions))
		{
			$this->error_goback(JText::sprintf("JEV_Invalid_TYPE", $uploadedFile["type"]));
		}

		$ftmp = $uploadedFile['tmp_name'];
		$oname = $uploadedFile['name'];

		$fileName = $oname;

		$fileName = uniqid(null, true) . "." . $suffix;

		$folder = JRequest::getVar('folder', '', '', 'path');

		$filelocation = JEVP_MEDIA_BASE .'/'. $folder;

		$this->ensureDirExists($filelocation);

		$fname = $filelocation .'/'. $fileName;
		jimport("joomla.filesystem.file");
		if (!JFile::copy($ftmp, $fname))
		{
			if (!rename($ftmp, $fname))
			{
				$this->error_goback(JText::_("JEV_Could_not_save"));
			}
		}
		@chmod($fname, 0644);

		return $fileName;

	}

	function processImageUpload($file)
	{
		set_time_limit(1800);

		if (!array_key_exists("HTTP_REFERER", $_SERVER) || (strpos($_SERVER["HTTP_REFERER"], "http://" . $_SERVER["HTTP_HOST"]) !== 0 && strpos($_SERVER["HTTP_REFERER"], "https://" . $_SERVER["HTTP_HOST"]) !== 0))
		{
			die();
		}

		$files = JFactory::getApplication()->input->files;
		$uploadedFile = $files->get($file, false);
		if (!$uploadedFile || $uploadedFile['error'] == UPLOAD_ERR_NO_FILE)
		{
			$this->error_goback(JText::_("JEV_Missing_Image_File"));
		}

		// this should be set in config
		$maxsize = $this->params->get("maxupload");
		$imagesize = $uploadedFile["size"];
		if ($uploadedFile["size"] > $maxsize)
		{
			$this->error_goback(JText::sprintf("JEV_Image_Too_Large", $imagesize, $maxsize));
		}

		$this->securityCheck($uploadedFile);

		$suffix = "jpg";
		if (!$this->checkImageType($uploadedFile["type"], $suffix))
		{
			$this->error_goback(JText::sprintf("JEV_Invalid_TYPE", $uploadedFile["type"]));
		}
		$ftmp = $uploadedFile['tmp_name'];
		$oname = $uploadedFile['name'];

		$fileName = uniqid(null, true) . "." . $suffix;

		$folder = $this->params->get("folder", "jevents");

		$filelocation = JEVP_MEDIA_BASE .'/'. $folder;

		$this->ensureDirExists($filelocation);

		$fname = $filelocation . '/' . $fileName;

		jimport("joomla.filesystem.file");
		// Copy original - will be used later for re-scaling
		$origdir = dirname($fname) . DIRECTORY_SEPARATOR . "originals";
		$this->ensureDirExists($origdir);

		$orig_file = $origdir . DIRECTORY_SEPARATOR . "orig_" . basename($fname);
		if (!JFile::copy($ftmp, $orig_file) && !copy($ftmp, $orig_file))
		{
			$this->error_goback(JText::_("JEV_problems_copying_image") . " $ftmp to $orig_file ");
			return false;
		}

		if (!JFile::copy($ftmp, $fname))
		{
			if (!rename($ftmp, $fname))
			{
				$this->error_goback(JText::_("JEV_could_not_save"));
			}
		}
		@chmod($fname, 0644);

		$imagew = $this->params->get("imagew");
		$imageh = $this->params->get("imageh");
		$no_thumbanil = $this->params->get("no_thumbanil");
		if ($imagew > 0 && $imageh > 0)
		{
			$this->scaleImage($fname, $imagew, $imageh, $imagesize, $no_thumbanil, false);
		}

		// create the thumbnail
		$thumbw = $this->params->get("thumbw");
		$thumbh = $this->params->get("thumbh");
		$this->scaleImage($fname, $thumbw, $thumbh, $imagesize, $no_thumbanil, true);

		return $fileName;

	}

	function error_goback($msg)
	{
		$backtrace = debug_backtrace();
		$isCustomField = false;
		foreach ($backtrace as $trace)
		{
			if (strpos($trace["file"], "customfields") > 0)
			{
				$isCustomField = true;
				break;
			}
		}
		?>
		<html>
			<head>
				<script  type="text/javascript">
					alert("<?php echo $msg; ?>");
		<?php if (!$isCustomField)
			echo "history.go(-1);"; ?>
				</script>
			</head>
			<body>
			</body>
		</html>
		<?php
		exit();

	}

	function checkImageType($type, &$suffix)
	{
		static $allowedImageTypes = array("image/png", "image/jpeg", "image/pjpeg", "image/gif");
		if (!in_array($type, $allowedImageTypes))
		{
			return false;
		}
		$suffix = str_replace("image/", "", $type);
		return true;

	}

	function checkFileType($type, $filename, &$suffix, $allowedfileExtensions = array("flv", "xml"))
	{
		//static $allowedfileExtensions = array("pdf","xls","xlsx","doc","docx","flv");
		//static $allowedfileExtensions = array("flv", "xml");
		if (strrpos($filename, ".") > 0)
		{
			$suffix = strtolower(substr($filename, strrpos($filename, ".") + 1));
			if (in_array($suffix, $allowedfileExtensions))
			{
				return true;
			}
		}
		return false;

	}

	function securityCheck($file)
	{
		if (!is_uploaded_file($file["tmp_name"]))
		{
			//$this->error_goback("Problems uploading file <b>".$file['name']."</b>");
			// Failed for various reasons
			switch ($file["error"]) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_INI_SIZE:
					$this->error_goback("The uploaded file exceeds the upload_max_filesize directive (" . ini_get("upload_max_filesize") . ") in php.ini.");
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$this->error_goback("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
					break;
				case UPLOAD_ERR_PARTIAL:
					$this->error_goback("The uploaded file was only partially uploaded.");
					break;
				case UPLOAD_ERR_NO_FILE:
					$this->error_goback("No file was uploaded.");
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					$this->error_goback("Missing a temporary folder.");
					break;
				case UPLOAD_ERR_CANT_WRITE:
					$this->error_goback("Failed to write file to disk");
					break;
				default:
					$this->error_goback("Unknown File Error");
			}
		}

	}

	function scaleImage($img, $maxwidth=150, $maxheight=150, $imagesize=10, $nothumbnail=1500000, $thumb=true)
	{
		// scale the image
		jimport("joomla.image.image");

		try {
			$info = JImage::getImageFileProperties($img);
		}
		catch (Exception $e){
			$this->error_goback(JText::_("JEV_problems_creating_thumbnail"));
		}

		$origw = $info->width;
		$origh = $info->height;
		// TODO thumbnail size in config
		// don't make thumbnail bigger than original!!
		if ($origw <= $maxwidth && $origh <= $maxheight)
		{
			$thumbWidth = $origw;
			$thumbHeight = $origh;
		}
		else
		{
			$thumbWidth = $maxwidth;
			$thumbHeight = intval($origh * $thumbWidth / $origw);
			if ($thumbHeight > $maxheight)
			{
				$thumbHeight = $maxheight;
				$thumbWidth = intval($origw * $thumbHeight / $origh);
			}
		}

		$imgtypes = array(1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC', 10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF', 15 => 'WBMP', 16 => 'XBM');

		// GD can only handle JPG & PNG images
		if ($info->type >= 4)
		{
			$this->error_goback(JText::_("JEV_ERROR_FILE_TYPE"));
		}

		// Create the thumbnail
		jimport("joomla.image.image");

		$src_img = new JImage($img);

		if (!$src_img)
		{
			$this->error_goback(JText::_("JEV_problems_creating_thumbnail") . " 4");
			return false;
		}


		// TODO set thumbnail directories in config
		if ($thumb)
		{

			$dst_img = $src_img->cropResize($thumbWidth, $thumbHeight, true);

			$thumbdir = dirname($img) . DIRECTORY_SEPARATOR . "thumbnails";
			$dest_file = $thumbdir . DIRECTORY_SEPARATOR . "thumb_" . basename($img);

			$this->ensureDirExists($thumbdir);

			if (!$dst_img->toFile($dest_file, $info->type))
			{
				$this->error_goback(JText::_("JEV_problems_creating_thumbnail") . " 4.5 ");
				return false;
			}

		}
		else
		{
			$thumbdir = dirname($img);
			$dest_file = $thumbdir . DIRECTORY_SEPARATOR . basename($img);

			$this->ensureDirExists($thumbdir);

			$dst_img = $src_img->cropResize($thumbWidth, $thumbHeight, true);

			if (!$dst_img)
			{
				$this->error_goback(JText::_("JEV_problems_scaling_image") . " 5");
				return false;
			}

			if (!$dst_img->toFile($dest_file, $info->type))
			{
				$this->error_goback(JText::_("JEV_problems_scaling_imag") . " 6 ");
				return false;
			}

		}
		// remove copies in memory
		$src_img->destroy();
		$dst_img->destroy();

		// We check the file exists
		if (!file_exists($dest_file))
		{
			$this->error_goback(JText::_("JEV_PROBLEMS_SCALING_IMAGE_OR_CREATING_THUMBNAIL") . " 8");
			return false;
		}
		// We check that the image is valid
		$imginfo = getimagesize($dest_file);
		if ($imginfo == null)
		{
			$this->error_goback(JText::_("JEV_PROBLEMS_SCALING_IMAGE_OR_CREATING_THUMBNAIL") . " 9");
			return false;
		}
		else
		{
			@chmod($dest_file, 0644);
			return true;
		}

	}

	function ensureDirExists($targetDir)
	{
		clearstatcache();
		jimport('joomla.filesystem.folder');
		if (!JFolder::exists($targetDir))
		{
			if (!JFolder::create($targetDir))
			{
				$this->error_goback("can't create directory $targetDir");
			}
		}

	}

	private function human_filesize($bytes, $decimals = 2)
	{
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];

	}

}