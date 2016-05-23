<?php 
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: edit16.php 1343 2010-10-20 14:31:24Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');
?>
	<form action="index.php" method="post" name="adminForm" autocomplete="off" id="adminForm">

		<fieldset class='jevconfig'>
			<legend>
				<?php echo JText::_( 'RSVP_CONFIG' );?>
			</legend>
			<?php
			$names = array();
			$groups = $this->params->getGroups();
			if (count($groups)>0){
				jimport('joomla.html.pane');
				$tabs =  JPane::getInstance('tabs');
				echo $tabs->startPane( 'configs' );
				$strings=array();
				$tips=array();
				foreach ($groups as $group=>$count) {
					if ($group!="xmlfile" && $count>0){
						echo $tabs->startPanel( JText::_($group), 'config_'.str_replace(" ","_",$group));
						if ($group=="JCONFIG_PERMISSIONS_LABEL"){
							$fieldSets = $this->form->getFieldsets();
							foreach ($fieldSets as $name => $fieldSet) {
								foreach ($this->form->getFieldset($name) as $field) {
									echo $field->label;
									echo $field->input;
								}
							}

						}
						else {
							echo $this->params->render('params',$group);							
						}
						echo $tabs->endPanel();
					}
				}

				// Now get layout specific parameters
				foreach (JEV_CommonFunctions::getJEventsViewList() as $viewfile) {
					$config = JPATH_SITE . "/components/".RSVP_COM_COMPONENT."/views/".$viewfile."/config.xml";
					if (file_exists($config)){
						$viewparams = new JevParameter( $this->params->toString(), $config );
						echo $tabs->startPanel( JText::_(ucfirst($viewfile)), 'config_'.str_replace(" ","_",$viewfile));
						echo $viewparams->render();
						echo $tabs->endPanel();
					}
				}

				echo $tabs->endPane();
			}
			else {
				echo $this->params->render();
			}

		?>
	
		<div class="clr"></div>
	        
		</fieldset>

		<input type="hidden" name="id" value="<?php echo version_compare(JVERSION, "1.6.0", 'ge')? $this->component->extension_id: $this->component->id;?>" />
		<input type="hidden" name="component" value="<?php echo version_compare(JVERSION, "1.6.0", 'ge')? $this->component->element: $this->component->option;?>" />

		<input type="hidden" name="controller" value="component" />
		<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT;?>" />
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_( 'form.token' ); ?>
	</form>