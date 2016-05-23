<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1399 2009-03-30 08:31:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminTemplatesViewTemplates extends RSVPAbstractView
{
	
	function __construct($config = null)
	{
		parent::__construct($config);
		// always load mootools!
		JHtml::_('behavior.framework', true);

	}
	
	/**
	 * Templates display function
	 *
	 * @param template $tpl
	 */
	function overview($tpl = null)
	{
		JHtml::stylesheet(  'components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );

		$document = JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_RSVP') . ' :: ' .JText::_('RSVP_SESSION_TEMPLATES'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'RSVP_SESSION_TEMPLATES' ), 'jevents' );

		//JToolBarHelper::publishList('templates.publish');
		//JToolBarHelper::unpublishList('templates.unpublish');
		JToolBarHelper::addNew('templates.edit');
		JToolBarHelper::editList('templates.edit');
		JToolBarHelper::custom('templates.editcopy','copy.png','copy.png','RSVP_COPYEDIT');
		JToolBarHelper::deleteList('RSVP_DELETE_ALL','templates.delete');
		JToolBarHelper::spacer();
		if (JFactory::getApplication()->isAdmin()){
			JToolBarHelper::custom( 'cpanel.cpanel', 'cancel.png', 'cancel.png', 'Control_Panel', false );
		}

		$this->showToolBar();

		RsvpproHelper::addSubmenu();

		$mainframe = JFactory::getApplication();$option=JRequest::getCmd("option");
		$filter_order		= $mainframe->getUserStateFromRequest( $option.'rsvp_filter_order',		'filter_order',		'tmpl.title',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'rsvp_filter_order_Dir',	'filter_order_Dir',	'',				'word' );
		$search				= $mainframe->getUserStateFromRequest( $option.'rsvp_search',			'search',			'',				'string' );
		$search				= JString::strtolower( $search );
		$customised			= $mainframe->getUserStateFromRequest( $option.'rsvp_customised',			'customised',			0,				'int' );
		$limit		= intval( $mainframe->getUserStateFromRequest( "templatelistlimit", 'limit', JFactory::getApplication()->get("list_limit", 10)));
		$limitstart = JRequest::getInt('limitstart', 0 );

		$lists = array();
		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		// search & customised filter
		$lists['search']= $search;
		$lists['customised']= $customised;

		// state filter
		$lists['state']	= "";//JHtml::_('grid.state',  $filter_state );

		JHtml::_('behavior.tooltip');

		$mainframe = JFactory::getApplication();

		$db		= JFactory::getDBO();
		$uri	= JFactory::getURI();

		// Get data from the model
		$model	= $this->getModel();
		$items		=  $this->get( 'Data');
		$total		=  $this->get( 'Total');

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit  );

		$jjuser = JFactory::getUser();
		$this->assignRef('user',		$jjuser);
		$this->assignRef('items',		$items);
		$this->assignRef('lists',		$lists);
		$this->assignRef('pageNav',	$pageNav);

		// Only offer translations in latest version of Joomla
		if (JevJoomlaVersion::isCompatible("3.4")){
			$this->languages = $this->get('Languages');
		}
		else {
			$this->languages = null;
		}

		parent::displaytemplate($tpl);


	}

	function edit($tpl = null){

		include_once(JPATH_ADMINISTRATOR.'/'."includes".'/'."toolbar.php");
		JHtml::stylesheet(  'components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );

		JHtml::_('jquery.framework');
		JHtml::_('jquery.ui', array('core', 'sortable'));
		JHtml::script( 'components/'.RSVP_COM_COMPONENT.'/assets/js/forms.js' );

		$document = JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_EDIT_TEMPLATE'));

		// Set toolbar items for the page

		if (JRequest::getString("task")=="templates.editcopy"){
		JToolBarHelper::title(JText::_( 'RSVP_EDIT_COPY_TEMPLATE' ), 'jevents' );
			JToolBarHelper::save('templates.savecopy');
		}
		else {
		JToolBarHelper::title(JText::_( 'RSVP_EDIT_TEMPLATE' ), 'jevents' );
		JToolBarHelper::apply('templates.apply',"RSVP_APPLY");
                JToolBarHelper::save('templates.save');
		}
		if (JFactory::getApplication()->isAdmin() || JRequest::getInt("customise")==0){
			JToolBarHelper::cancel('templates.cancel');
		}

		$this->showToolBar();

		RsvpproHelper::addSubmenu();
		
		JHtml::_('behavior.tooltip');

		$mainframe = JFactory::getApplication();

		$db		= JFactory::getDBO();
		$uri	= JFactory::getURI();
		
		// Get data from the model
		$model	= $this->getModel();
		$item	=  $this->get( 'Data');
		$this->params	= $model->getParams($item);
		$templateparams = $item->params;
		if (is_string($templateparams) && strlen(trim($templateparams))>2){
			$templateparams = new JRegistry($templateparams);
		}
		else if (is_string($templateparams) && trim($templateparams)==""){
			$templateparams = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		}
		$this->templateparams =$templateparams ;
		
		$hasAttendeesToLock = $model->hasAttendeesToLock($item);

		$this->setLayout("edit");
		$this->assignRef('item',		$item);
		
		$this->assign('hasAttendeesToLock', $hasAttendeesToLock);
		
		// Extra call for the RSVP Pro Pop-Up edit window to allow CSS tweaks.
		if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css"))
		{
			// It is definitely now created, lets load it!
			JEVHelper::stylesheet('jevcustom.css', 'components/' . JEV_COM_COMPONENT . '/assets/css/');
		}
		
		// Extra call for the RSVP Pro Pop-Up edit window to allow CSS tweaks.
		if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css"))
		{
			// It is definitely now created, lets load it!
			JEVHelper::stylesheet('jevcustom.css', 'components/' . JEV_COM_COMPONENT . '/assets/css/');
		}
		
		// Extra call for the RSVP Pro Pop-Up edit window to allow CSS tweaks.
		if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css"))
		{
			// It is definitely now created, lets load it!
			JEVHelper::stylesheet('jevcustom.css', 'components/' . JEV_COM_COMPONENT . '/assets/css/');
		}
		
		parent::displaytemplate($tpl);

	}


	function showToolBar(){
		$mainframe = JFactory::getApplication();
		if (JRequest::getVar("tmpl","")=="component" || !$mainframe->isAdmin()){
			?>
		<div class='jevrsvppro'>
				<div id="toolbar-box" >
					<div class="t">
					<div class="t">
						<div class="t"></div>
					</div>
				</div>
				<div class="m">
				<?php
				$bar =  JToolBar::getInstance('toolbar');
				$barhtml = $bar->render();
				$barhtml = preg_replace('/onclick="(.*)" /','onclick="$1;return false;" ',$barhtml);
				echo $barhtml;
				if (version_compare(JVERSION, "3.0", 'ge')){
					$title = JFactory::getApplication()->JComponentTitle;
				}
				else {
					$title = JFactory::getApplication()->get('JComponentTitle');
				}
				
				echo $title;
				?>
				<div class="clr"></div>
				</div>
				<div class="b">
					<div class="b">
						<div class="b"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
		}
		// Kepri doesn't load icons etc. when using tmpl=component - but we want them!
		if (JRequest::getVar("tmpl","")=="component" && $mainframe->isAdmin()){
			JHtml::stylesheet( 'administrator/templates/'.$mainframe->getTemplate().'/css/template.css' );

		}
	}

	function _globalHTML(&$row, $i){
		$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';

		$img 	= $row->global ? 'Tick.png':  'Cross.png';
		$alt 	= $row->global ? JText::_( 'Global' ) : JText::_( 'User' );

		$mainframe = JFactory::getApplication();
		$img = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $alt . '" />';

		$action = $row->global ? JText::_( 'RSVP_MAKE_TEMPLATE_PRIVATE' ) : JText::_( 'RSVP_MAKE_TEMPLATE_COMMON' );
		$task = $row->global ? "templates.privatise":"templates.globalise";

		if (JevTemplateHelper::canCreateGlobal())
		{
			$href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
			'.$img.'</a>'	;
		}
		else {
			$href = $img;
		}
		
		return $href;
	}

	function _lockedHTML(&$row, $i){
		$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';

		$img 	= $row->locked ? 'Tick.png':  'Cross.png';
		$alt 	= $row->locked ? JText::_( 'RSVP_LOCKED' ) : JText::_( 'RSVP_UNLOCKED' );

		$mainframe = JFactory::getApplication();
		$img = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $alt . '" />';

		$action = $row->locked ? JText::_( 'RSVP_UNLOCK_TEMPLATE' ) : JText::_( 'RSVP_LOCK_TEMPLATE' );
		$task = $row->locked ? "templates.unlock":"templates.lock";

		if (JevTemplateHelper::canCreateGlobal())
		{
			$href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
			'.$img.'</a>'	;
		}
		else {
			$href = $img;
		}
		
		return $href;
	}
	
	function isTemplateHTML(&$row, $i){
		$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';

		$img 	= $row->istemplate? 'Tick.png':  'Cross.png';
		$alt 	= $row->istemplate ? JText::_( 'Global' ) : JText::_( 'User' );

		$mainframe = JFactory::getApplication();
		$img = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $alt . '" />';

		$action = $row->istemplate ? JText::_( 'RSVP_UNMAKE_TEMPLATE' ) : JText::_( 'RSVP_MAKE_TEMPLATE' );
		$task = $row->istemplate ? "templates.unmaketemplate":"templates.maketemplate";

		if (JevTemplateHelper::canCreateGlobal())
		{
			$href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
			'.$img.'</a>'	;
		}
		else {
			$href = $img;
		}
		
		return $href;
	}

	function translate($tpl = null)
	{

		if (!version_compare(JVERSION, "3.4", 'ge')) 	{
			echo "You need Joomla 3.4+ to do that<br/>";
			return;
		}

		JHtml::_('bootstrap.framework');
		JHtml::_('bootstrap.loadCss');

		JHtml::stylesheet(  'components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );

		JHtml::script( 'components/'.RSVP_COM_COMPONENT.'/assets/js/forms.js' );

		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);

		$this->editor =  JFactory::getEditor();
		if ($this->editor->get("_name") == "codemirror")
		{
			$this->editor = JFactory::getEditor("none");
			JFactory::getApplication()->enqueueMessage(JText::_("JEV_CODEMIRROR_NOT_COMPATIBLE_EDITOR", "WARNING"));
		}

		// Get the form && data
		$model	= $this->getModel();
		//$this->form = $this->get('TranslateForm');
		$this->item = $this->get("Translation");

		$this->params	= $model->getParams($this->item);
		$templateparams = $this->item->params;
		if (is_string($templateparams) && strlen(trim($templateparams))>2){
			$templateparams = new JRegistry($templateparams);
		}
		else if (is_string($templateparams) && trim($templateparams)==""){
			$templateparams = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		}
		$this->templateparams =$templateparams ;

		$this->lang = JRequest::getString("lang", "");
		$this->assign('hasAttendeesToLock', 0);

		$this->addTranslationToolbar();

		$this->setLayout("translate");

		parent::displaytemplate($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addTranslationToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		JToolbarHelper::save('templates.savetranslation');
		JToolbarHelper::cancel('templates.close');
		$bar =  JToolBar::getInstance('toolbar');

		// Add a standard button
		$bar->appendButton('confirm', JText::_("RSVP_DELETE_TRANSLATION_WARNING"),  'trash',  'JEV_DELETE', "templates.deletetranslation", false);

	}


	protected function translationLinks ($row)
	{
		if ($this->languages)
		{
			JLoader::register('JevModal',JPATH_LIBRARIES."/jevents/jevmodal/jevmodal.php");
			JevModal::modal(".rsvp-translations .jevmodal");
			JEVHelper::stylesheet('eventsadmin.css', 'components/com_jevents/assets/css/');
			
			// Any existing translations ?
			$db = JFactory::getDbo();
			$db->setQuery("SELECT language FROM #__jev_rsvp_template_translation where template_id= ".$row->id);
			$translations = $db->loadColumn();
			// test styling for existing translation
			//$translations[] = "cy-GB";
			?>
			<ul class="rsvp-translations item-associations">
			<?php foreach ($this->languages as $id => $item) :

				$text = strtoupper($item->sef);
				$url = JRoute::_('index.php?option=com_rsvppro&task=templates.translate&template_id='.$row->id.'&pop=1&tmpl=component&lang=' . $item->lang_code);
				$img = JHtml::_('image', 'mod_languages/' . $item->image . '.gif',
						$item->title,
						array('title' => $item->title),
						true
					);
				$url  = "javascript:jevModalPopup('translationPopup', '".$url ."', '". JText::sprintf("JEV_TRANSLATE_TEMPLATE_TO" ,  addslashes($item->title),  array('jsSafe'=>true) ) . "'); ";
				$tooltipParts = array( 	$img,  $item->title);
				$item->link = JHtml::_('tooltip', implode(' ', $tooltipParts), null, null, $text, $url, null, 'hasTooltip label label-association jevmodal-link label-' . $item->sef .( in_array($item->lang_code, $translations)?" hastranslation":"" ));
				?>
				<li>
				<?php
				echo $item->link;
				?>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php
		}
	}


}
