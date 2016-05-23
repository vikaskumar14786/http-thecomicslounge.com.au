<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
JLoader::register('JevJoomlaVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");

if (!version_compare(JVERSION, "1.6.0", 'ge'))
{

	class JElementJevtemplate extends JElement
	{

		/**
		 * Element name
		 *
		 * @access	protected
		 * @var		string
		 */
		var $_name = 'Jevtemplate';

		function fetchElement($name, $value, &$node, $control_name)
		{
			JFactory::getLanguage()->load('plg_jevents_jevcustomfields', JPATH_ADMINISTRATOR);

			$content = "";
			jimport("joomla.filesystem.file");
			$templates = JFolder::files(dirname(__FILE__) . "/templates/", ".xml");
			// only offer extra fields templates if there is more than one available
			if (count($templates) > 1)
			{

				// this loads the language strings ! BIZZARE!
				JPluginHelper::importPlugin('jevents');
				// I can't do this since it only returns published plugins and I may want to configure an unpublished plugin!
				//$plugin = JPluginHelper::getPlugin("jevents","jevcustomfields");
				$db = JFactory::getDBO();
				$db->setQuery('SELECT folder AS type, element AS name, params  FROM #__plugins where folder="jevents" and element="jevcustomfields" ');
				$plugin = $db->loadObject();
				ob_start();
?>
				<fieldset>
					<legend><?php echo JText::_("JEV_EXTRA_FIELDS"); ?></legend>
			<div>
				<label for="custom_rsvp_template" class='label'><?php echo JText::_("JEV_EXTRA_FIELDS_TEMPLATE"); ?></label>
		<?php
				$options = array();
				$options[] = JHTML::_('select.option', "", JText::_("JEV_SELECT_TEMPLATE"), 'var', 'text');
				foreach ($templates as $template)
				{
					if ($template == "fieldssample.xml" || $template == "fieldssample16.xml"  || $template == "all_fields.xml")
						continue;
					$options[] = JHTML::_('select.option', $template, ucfirst(str_replace(".xml", "", $template)), 'var', 'text');
				}

				$value = "";
				if (!is_null($plugin))
				{
					$params = new JRegistry($plugin->params);
					$value = $params->get("template", "");
				}

				echo JHTML::_('select.genericlist', $options, $control_name . '[' . $name . ']', '', 'var', 'text', $value);
		?>
					</div>
				</fieldset>
<?php
				$content = ob_get_clean();
			}
			return $content;

			$rows = $this->attribute('rows');
			$cols = $this->attribute('cols');
			$class = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . '"' : 'class="text_area"' );
			// convert <br /> tags so they are not visible when editing
			$value = str_replace('<br />', "\n", JText::_($value));

			return '<textarea name="' . $control_name . '[' . $name . ']" cols="' . $cols . '" rows="' . $rows . '" ' . $class . ' id="' . $control_name . $name . '" >' . $value . '</textarea>';

		}

	}

}
else
{
	jimport('joomla.html.html');
	jimport('joomla.form.formfield');
	jimport('joomla.form.helper');
	JFormHelper::loadFieldClass('list');

	/**
	 * JEVMenu Field class for the JEvents Component
	 *
	 * @package		JEvents.fields
	 * @subpackage	com_banners
	 * @since		1.6
	 */
	class JFormFieldJEVTemplate extends JFormFieldList
	{

		/**
		 * The form field type.s
		 *
		 * @var		string
		 * @since	1.6
		 */
		protected $type = 'JEVTemplate';

		public function getInput() {
			JFactory::getLanguage()->load('plg_jevents_jevcustomfields', JPATH_ADMINISTRATOR);

			return parent::getInput();
		}

		public function getOptions()
		{
			// Initialize variables.
			$options = array();

			jimport('joomla.filesystem.folder');
			$templates = JFolder::files(dirname(__FILE__) . "/templates/", ".xml");
			// only offer extra fields templates if there is more than one available
			if (count($templates) > 0)
			{

				// this loads the language strings ! BIZZARE!
				JPluginHelper::importPlugin('jevents');
				
				$options = array();
				$options[] = JHTML::_('select.option', "", JText::_("JEV_SELECT_TEMPLATE"), 'value', 'text');
				foreach ($templates as $template)
				{
					if ($template == "fieldssample.xml" || $template == "fieldssample16.xml" || $template == "all_fields.xml")
						continue;
					$options[] = JHTML::_('select.option', $template, ucfirst(str_replace(".xml", "", $template)), 'value', 'text');
				}
			}
			return $options;
		}

	}

}