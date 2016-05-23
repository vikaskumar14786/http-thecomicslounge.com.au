<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class JFormFieldJevcfimage extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfimage';

	function __construct(){
		parent::__construct();
	}

	function getInput()
	{
		$plugin = JPluginHelper::getPlugin('jevents', 'jevfiles' );
		if (!$plugin) return "<strong>".JText::_("Please install the JEV Files Addon")."</strong>";

		// intercept this to save the file
		if (isset($_FILES) && count($_FILES)>0){

			$hasfile = false;
			$uploadfile = "";
			foreach ($_FILES as $key=>$val){
				if ((strpos($key,"upload_cimage")===0 || strpos($key,"upload_cfile")===0) && $val["size"]>0){
					$hasfile =true;
					$uploadfile = $key;
					break;
				}
			}

			if ($hasfile)	return $this->processUpload($uploadfile);
		}

		JFactory::getLanguage()->load( 'plg_jevents_jevfiles',JPATH_ADMINISTRATOR );

		$this->params = new JRegistry($plugin->params);

		// Get the media component configuration settings
		$mediaparams = JComponentHelper::getParams('com_media');
		// Set the path definitions
		if (!defined('JEVP_MEDIA_BASE')) {
			define('JEVP_MEDIA_BASE',    JPATH_ROOT."/".$mediaparams->get('image_path', 'images'."/".'stories'));
			define('JEVP_MEDIA_BASEURL', JURI::root().$mediaparams->get('image_path', 'images/stories'));
		}

		// folder relative to media folder
		$folder = $this->params->get("folder","");
		if ($folder=="") {
			echo JText::_("JEV_SAVE_PLUGIN_PARAMETERS");
			return;
		}
		// ensure folder exists
		if (!JFolder::exists(JEVP_MEDIA_BASE."/".$folder)) {
			JFolder::create(JEVP_MEDIA_BASE."/".$folder);
		}

		static $setscripts;
		static $result;
		if (!isset($setscripts)){
			$setscripts = 1;
			// need session id to ensure login is maintained
			$session = JFactory::getSession();
			$mainframe = JFactory::getApplication();
			$option = JRequest::getCmd("option", "com_jevents");

			if ($option=="com_jevents"){
				if (JFactory::getApplication()->isAdmin()){
					$targetURL = JURI::root().'administrator/index.php?&option=com_jevents&folder='.$folder.'&'.$session->getName().'='.$session->getId().'&'.JSession::getFormToken().'=1';
				}
				else {				
					$targetURL = JURI::root().'index.php?task=day.listevents&option=com_jevents&tmpl=component&folder='.$folder.'&'.$session->getName().'='.$session->getId().'&'.JSession::getFormToken().'=1';
				}
			}
			else if ($option=="com_jevlocations"){
				$targetURL = JURI::root().(JFactory::getApplication()->isAdmin()?"administrator/":"").'index.php?task=locations.edit&option=com_jevlocations&tmpl=component&folder='.$folder.'&'.JSession::getFormToken().'=1';
			}
			else if ($option=="com_jevpeople") {
				$targetURL = JURI::root().(JFactory::getApplication()->isAdmin()?"administrator/":"").'index.php?task=people.edit&option=com_jevpeople&tmpl=component&folder='.$folder.'&'.JSession::getFormToken().'=1';
			}
			$uploaderInit = "
			var oldAction = '';
			var oldTarget = '';
			var oldTask = '';
			var oldOption = '';
			function uploadFileTypeCustom(field){
				if ('".$option."'=='com_jevents') form = document.updateattendance || document.adminForm;
				else form = document.adminForm;
				oldAction = form.action;
				oldTarget = form.target;
				form.action = '".$targetURL."&field='+field;
				form.target = 'uplaodtargetci';
				form.submit();
				form.action = oldAction ;
				form.target = oldTarget ;
				
				var loading = document.getElementById(field+'_loading');
				loading.style.display='block';
				var loaded = document.getElementById(field+'_loaded');
				loaded.style.display='none';
			}
			
		function setImageFileNameCustom(){			
			var myiframe = frames.uplaodtargetci;
			if(!myiframe.fname) return;
			
			elemname =  myiframe.fname.substr(0,myiframe.fname.length-5);
			elem = document.getElementById('custom_' + elemname);
			if (elem) elem.value = myiframe.filename;
			elem = document.getElementById('jform_' + elemname);
			if (elem) elem.value = myiframe.filename;
			elem = document.getElementById('custom_' + elemname + '_title');
			if (elem) elem.value = myiframe.oname;
			elem = document.getElementById(myiframe.fname);
			if (elem) elem.value = '';
			img = document.getElementById(elemname+'_img');
			img.src = '". JEVP_MEDIA_BASEURL."/$folder/thumbnails/thumb_'+myiframe.filename;
			img.style.display='block';
			img.style.marginRight='10px';
			
			var loading = document.getElementById(elemname+'_loading');
			loading.style.display='none';
			var loaded = document.getElementById(elemname+'_loaded');
			loaded.style.display='block';
			
		}		
			
		function clearImageFileCustom(elemname){
		
			img = document.getElementById(elemname+'_img');
			img.src = ''
			img.style.display='none';
			img.style.marginRight='0px';
			elem = document.getElementById('custom_' + elemname);
			if (elem) elem.value = '';
			elem = document.getElementById('jform_' + elemname);
			if (elem) elem.value = '';
			elem = document.getElementById('custom_' + elemname+'_title');
			if (elem) elem.value = '';
		}
			";
			$document = JFactory::getDocument();
			$document->addScriptDeclaration($uploaderInit);

			$result = '<iframe src="about:blank" style="display:none" name="uplaodtargetci" id="uplaodtargetci"></iframe>';
		}
		else {
			$result = "";
		}

		$value = $this->value;
		if ($value != ""){
			$src = JEVP_MEDIA_BASEURL."/$folder/thumbnails/thumb_$value";
			$visibility = "display:block;";
		}
		else {
			$src = "about:blank";
			$visibility = "display:none;";
		}

		$name = (string) $this->attribute("name");
		$fieldname = "upload_cimage_".$name;
		$result .= '<img id="'.$fieldname.'_img" src="'.$src.'" style="float:left;'.$visibility.'"/>';
		$result .= '<input type="hidden" name="jform['.$name.']" id="custom_'.$fieldname.'" value="'.$value.'" size="50"/>';
		$result .= '<label for="'.$fieldname.'_file">'.JText::sprintf("JEV_UPLOAD_IMAGE",number_format($this->params->get("maxupload",1000000)/1000000,2)).'</label><br/>';
		$result .= '<input type="file" name="'.$fieldname.'_file" id="'.$fieldname.'_file" size="50"/>';
		$result .= ' <input type="button" onclick="uploadFileTypeCustom(\''.$fieldname.'\')" value="'.JText::_("jev_upload").'"/> ';
		$result .= '<input type="button" onclick="clearImageFileCustom(\''.$fieldname.'\')" value="'.JText::_("jev_Delete").'"/>';
		$result .= '<div id="'.$fieldname.'_loading" class="loading" style="display:none">'.JText::_("JEV_UPLOADING_FILE_WAIT") .'</div>';
		$result .= '<div id="'.$fieldname.'_loaded" class="loaded" style="display:none">'.JText::_("JEV_UPLOAD_COMPLETE").'</div>';
		$result .= '<br style="clear:both"/>';


		return $result;

		$size = ( $this->attribute('size') ? 'size="'.$this->attribute('size').'"' : '' );
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="text_area"' );
		/*
		* Required to avoid a cycle of encoding &
		* html_entity_decode was used in place of htmlspecialchars_decode because
		* htmlspecialchars_decode is not compatible with PHP 4
		*/
		$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);

		return '<input type="text" name="'.$this->name.'" id="'.$this->id.'" value="'.$value.'" '.$class.' '.$size.' />';
	}
	
	public function convertValue($value, $node){
		if ($value == "") return "";
		static $folder;
		if (!isset($folder)){
			// Get the media component configuration settings
			$params = JComponentHelper::getParams('com_media');
			// Set the path definitions
			if (!defined('JEVP_MEDIA_BASE')) {
				define('JEVP_MEDIA_BASE',    JPATH_ROOT."/".$params->get('image_path', 'images'."/".'stories'));
				define('JEVP_MEDIA_BASEURL', JURI::root().$params->get('image_path', 'images/stories'));
			}

			$plugin = JPluginHelper::getPlugin('jevents', 'jevfiles' );
			if (!$plugin) return "";
			$params = new JRegistry($plugin->params);
			// folder relative to media folder
			$folder = $params->get("folder","");
			if ($folder=="") {
				echo JText::_("JEV_SAVE_PLUGIN_PARAMETERS");
				return;
			}
			// ensure folder exists
			if (!JFolder::exists(JEVP_MEDIA_BASE."/".$folder)) {
				JFolder::create(JEVP_MEDIA_BASE."/".$folder);
			}
		}
		$this->imagefolder= $folder;
		$this->rawimagevalue = $value;
		return "<img src='".JEVP_MEDIA_BASEURL."/".$folder."/".$value."' alt='".$this->attribute('name')."' />";
	}

	public function fieldNameArray($layout="detail"){
		$labels = array();
		$values = array();
		$output = array();
		
		$labels[] = JText::_("JEV_CUSTOM_THUMBNAIL", true) == "JEV_CUSTOM_THUMBNAIL" ? "Thumbnail" : JText::_("JEV_CUSTOM_THUMBNAIL", true);
		$values[] = "THUMBNAIL";
		if (isset($this->rawimagevalue)){
			$output["THUMBNAIL"] = "<img src='".JEVP_MEDIA_BASEURL."/".$this->imagefolder."/thumbnails/thumb_".$this->rawimagevalue."' alt='".$this->attribute('name')."' />";
		}
		else {
			$output["THUMBNAIL"] = "";
		}
		
		$labels[] = JText::_("JEV_CUSTOM_POPUP", true) == "JEV_CUSTOM_POPUP" ? "Popup" : JText::_("JEV_CUSTOM_POPUP", true);
		$values[] = "POPUP";
		if (isset($this->rawimagevalue)){
			JHTML::_('behavior.modal');
			$img = JEVP_MEDIA_BASEURL."/".$this->imagefolder."/".$this->rawimagevalue;
			$thumb =JEVP_MEDIA_BASEURL."/".$this->imagefolder."/thumbnails/thumb_".$this->rawimagevalue;
			$output["POPUP"] = "<a class='modal' rel='{handler: \"image\",}' href='".$img."' ><img src='".$thumb."' class='jev_custimagethumb'  /></a>";
		}
		else {
			$output["POPUP"] = "";
		}
				
		return array("labels"=>$labels, "values"=>$values, "output"=>$output);
		
	} 
	
	private function  processUpload($uploadfile){

		// Check for request forgeries
		JRequest::checkToken( 'get' ) or jexit( 'Invalid Token' );

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		// Set the path definitions
		if (!defined('JEVP_MEDIA_BASE')) {
			define('JEVP_MEDIA_BASE',    JPATH_ROOT."/".$params->get('image_path', 'images'."/".'stories'));
			define('JEVP_MEDIA_BASEURL', JURI::root().$params->get('image_path', 'images/stories'));
		}

		if (version_compare(JVERSION, "1.6.0", 'ge')) {
			$pluginhelper=JPATH_SITE."/plugins/jevents/jevfiles/files/uploadhelper.php";
		}
		else {
			$pluginhelper=JPATH_SITE."/plugins/jevents/files/uploadhelper.php";
		}

		include_once($pluginhelper);
		$plugin = JPluginHelper::getPlugin("jevents","jevfiles");
		$jevfilesparams = new JRegistry($plugin->params);

		// Get some important attributes from the form field element.
		$imageh = (integer) $this->attribute('height');
		$imagew = (integer) $this->attribute('width');
		$thumbh = (integer) $this->attribute('thumbheight');
		$thumbw = (integer) $this->attribute('thumbwidth');

		if ($imageh) $jevfilesparams->set ("imageh", $imageh);
		if ($imagew) $jevfilesparams->set ("imagew", $imagew);
		if ($thumbh) $jevfilesparams->set ("thumbh", $thumbh);
		if ($thumbw) $jevfilesparams->set ("thumbw", $thumbw);

		$uploadhelper = new JevUploadHelper($jevfilesparams);
		$filename = $uploadfile;
		foreach ($_FILES as $fname=>$file) {
			if (strpos($fname,$filename)===0){
				if (strpos($uploadfile,"upload_cimage")===0){
					$filename = $uploadhelper->processImageUpload($fname);
					$filetype = "image";
				}
				else {
					$filename = $uploadhelper->processFileUpload($fname,".xml",explode(",",$jevfilesparams->get("allowedfiles","csv,xml,pdf,doc,xls")));
					$filetype = "file";
				}
				$oname = $_FILES[$fname]['name'];
				?>
<script  type="text/javascript">
var oname = "<?php echo $oname;?>";
var fname = "<?php echo $fname;?>";
var filename = "<?php echo $filename;?>";
var filetype = "<?php echo $filetype;?>";
<?php if ($filetype=="image"){ ?>
window.parent.setImageFileNameCustom();
<?php } else { ?>
window.parent.setLinkFileHref();
<?php } ?>
</script>
				<?php
			}
		}
		return;

	}

	public function attribute($attr){
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val)?(string)$val:null;
		return $val;
	}

	/**
	 * Magic setter; allows us to set protected values
	 * @param string $name
	 * @return nothing
	 */
	public function setValue($value) {
		$this->value = $value;
	}
}