<?php
/**
 * Copyright (C)2010-2015 GWE Systems Ltd
 *
 * All rights reserved.
 *
 */
defined('_JEXEC') or die('No Direct Access');

jimport("joomla.html.pagination");

class RsvpHelper
{

	static function canCreateSessions()
	{
		$params = JComponentHelper::getParams('com_jevents');
		$authorisedonly = $params->get("authorisedonly", 0);
		if (!$authorisedonly)
		{
			$plugin = JPluginHelper::getPlugin("jevents", "jevrsvppro");
			$rsvpparams = JComponentHelper::getParams('com_rsvppro');
			$creatersvp = $rsvpparams->get("creatersvp", 25);
			$juser =  JFactory::getUser();
			if (version_compare(JVERSION, "1.6.0", 'ge'))
			{
				if ($juser->authorise('core.createreg', 'com_rsvppro'))
				{
					return true;
				}
			}
			else if ($juser->gid >= intval($creatersvp))
			{
				return true;
			}
		}
		else
		{
			$jevuser =  JEVHelper::getAuthorisedUser();
			if ($jevuser && $jevuser->cancreateown)
			{
				// if jevents is not in authorised only mode then switch off this user's permissions
				return true;
			}
		}
		return false;

	}

	static function accessOptions($id, $field)
	{
		$fieldname = "fa[$id]";
		$value = $field ? $field->access : 0;
		$params = JComponentHelper::getParams("com_rsvppro");
		$style = $params->get("allowaccessrestrictions",1)?'' : 'style="display:none"';
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel" <?php echo $style;?> ><?php echo JText::_("RSVP_ACCESS"); ?></div>
		<div class="rsvpinputs  radio btn-group"  <?php echo $style;?> >
			<?php
			include_once(JPATH_SITE . "/components/com_jevents/libraries/jeventshtml.php");
			if (is_callable(array("JEventsHTML", "buildAccessSelect")))
			{
				if (version_compare(JVERSION, "1.6.0", 'ge'))
				{
					echo JEventsHTML::buildAccessSelect(intval($value), 'class="inputbox" size="1" ', "", $fieldname);
				}
				else
				{
					echo JEventsHTML::buildAccessSelect(intval($value), 'class="inputbox" size="1"  ', "", $fieldname);
				}
			}
			else
			{
				static $groups;
				if (!isset($groups))
				{
					// get list of groups
					$db = JFactory::getDBO();
					$query = "SELECT id AS value, name AS text"
							. "\n FROM #__groups"
							. "\n ORDER BY id";
					$db->setQuery($query);
					$groups = $db->loadObjectList();
				}

				// build the html select list
				echo JHtml::_('select.genericlist', $groups, $fieldname, 'class="inputbox" size="1"', 'value', 'text', $value);
			}


			// field access flag - everyone apart from members of this group can access if this has value 1
			$fieldname = "faf[$id]";
			$value = $field ? $field->accessflag : 1;
			?>
		</div>
		<div class="rsvplabel" <?php echo $style;?> ></div>
		<div class="rsvpinputs  radio btn-group"   <?php echo $style;?> >
			<label for="accessflag1<?php echo $id; ?>" class="btn radio" ><?php echo JText::_("RSVP_ALLOWED_ACCESS"); ?>
			<input type="radio" name="accessflag[<?php echo $id; ?>]"    id="accessflag1<?php echo $id; ?>" value="1" <?php
		if ($field && $field->accessflag == 1)
		{
			echo 'checked="checked"';
		}
		if (!$field)
		{
			echo 'checked="checked"';
		}
			?> />
			</label>
			<label for="accessflag0<?php echo $id; ?>" class="btn radio" ><?php echo JText::_("RSVP_ACCESS_BLOCKED"); ?>
			<input type="radio" name="accessflag[<?php echo $id; ?>]"   id="accessflag0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->accessflag == 0)
		   {
			   echo 'checked="checked"';
		   }
			?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function required($id, $field, $default = 0)
	{
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><?php echo JText::_("RSVP_IS_REQUIRED"); ?></div>
		<div class="rsvpinputs radio btn-group">
			<label for="rr1<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio" name="rr[<?php echo $id; ?>]"  id="rr1<?php echo $id; ?>" value="1" <?php
		if (($field && $field->required == 1) || (!$field && $default) )
		{
			echo 'checked="checked"';
		}
		?> />
			</label>
			<label for="rr0<?php echo $id; ?>"  class="btn radio"><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio" name="rr[<?php echo $id; ?>]" id="rr0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->required == 0)
		   {
			   echo 'checked="checked"';
		   }
		   if (!$field && !$default)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function requiredMessage($id, $field)
	{
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><label for="rm<?php echo $id; ?>"><?php echo JText::_("RSVP_REQUIRED_MESSAGE"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="rm[<?php echo $id; ?>]" id="rm<?php echo $id; ?>" value="<?php echo $field ? $field->requiredmessage : ''; ?>" size="40" maxlength="255" />
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function allowoverride($id, $field)
	{
		return "";
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><?php echo JText::_("RSVP_ALLOW_OVERRIDE"); ?></div>
		<div class="rsvpinputs  radio btn-group">
			<label for="ao1<?php echo $id; ?>" class="btn radio" ><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio"   name="ao[<?php echo $id; ?>]"  id="ao1<?php echo $id; ?>" value="1" <?php
		if ($field && $field->allowoverride == 1)
		{
			echo 'checked="checked"';
		}
		?> />
			</label>
			<label for="ao0<?php echo $id; ?>" class="btn radio" ><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio"  name="ao[<?php echo $id; ?>]" id="ao0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->allowoverride == 0)
		   {
			   echo 'checked="checked"';
		   }
		   if (!$field)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function tooltip($id, $field)
	{
		?>
		<div class="rsvplabel"><label for="ft<?php echo $id; ?>"><?php echo JText::_("RSVP_FIELD_TOOLTIP"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="ft[<?php echo $id; ?>]" id="ft<?php echo $id; ?>" value="<?php echo $field ? htmlspecialchars($field->tooltip) : ''; ?>" size="40" maxlength="255" />
		</div>
		<div class="rsvpclear"></div>
		<?php

	}

	static function size($id, $field, $fieldtype)
	{
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><label for="size<?php echo $id; ?>"><?php echo JText::_("RSVP_FIELD_SIZE"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="size[<?php echo $id; ?>]" id="size<?php echo $id; ?>" value="<?php echo $field && $field->size > 0 ? $field->size : 10; ?>" size="5" maxlength="5"
				   onchange="<?php echo $fieldtype; ?>.changeSize('<?php echo $id; ?>')"    onkeyup="<?php echo $fieldtype; ?>.changeSize('<?php echo $id; ?>')" />
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function maxlength($id, $field, $fieldtype)
	{
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><label for="maxlength<?php echo $id; ?>"><?php echo JText::_("RSVP_FIELD_MAXLENGTH"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="maxlength[<?php echo $id; ?>]" id="maxlength<?php echo $id; ?>" value="<?php echo $field && $field->maxlength > 0 ? $field->maxlength : 20; ?>" size="5" maxlength="5"
				   onchange="<?php echo $fieldtype; ?>.changeMaxlength('<?php echo $id; ?>')" />
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function cols($id, $field, $fieldtype)
	{
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><label for="cols<?php echo $id; ?>"><?php echo JText::_("RSVP_FIELD_COLS"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="cols[<?php echo $id; ?>]" id="cols<?php echo $id; ?>" value="<?php echo $field && $field->cols > 0 ? $field->cols : 20; ?>" size="5" maxlength="5"
				   onchange="<?php echo $fieldtype; ?>.changeCols('<?php echo $id; ?>')" />
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function rows($id, $field, $fieldtype)
	{
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><label for="rows<?php echo $id; ?>"><?php echo JText::_("RSVP_FIELD_ROWS"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="rows[<?php echo $id; ?>]" id="rows<?php echo $id; ?>" value="<?php echo $field && $field->rows > 0 ? $field->rows : 5; ?>" size="5" maxlength="5"
				   onchange="<?php echo $fieldtype; ?>.changeRows('<?php echo $id; ?>')" />
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function label($id, $field, $fieldtype="")
	{
		?>
		<div class="rsvplabel"><label for="fl<?php echo $id; ?>"><?php echo JText::_("RSVP_FIELD_LABEL"); ?></label></div>
		<div class="rsvpinputs">
			<input type="text" name="fl[<?php echo $id; ?>]" id="fl<?php echo $id; ?>" value="<?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?>" size="40" maxlength="255" class="rsvpfl"
				   onkeyup="rsvppro.updateLabel('<?php echo $id; ?>');"  rel="<?php echo $fieldtype; ?>"/>
		</div>
		<div class="rsvpclear"></div>
		<?php

	}

	static function fieldId($id)
	{
		return "";
		echo " {" . $id . "}";

	}

	static function hidden($id, $field, $name)
	{
		?>
		<input type="hidden" name="type[<?php echo $id; ?>]" id="type<?php echo $id; ?>" value="<?php echo $name; ?>" />
		<input type="hidden" name="fid[<?php echo $id; ?>]" id="fid<?php echo $id; ?>" value="<?php echo $field ? $id : 0; ?>" />
		<input type="hidden" name="ordering[<?php echo $id; ?>]" id="ordering<?php echo $id; ?>" value="<?php echo $field ? $field->ordering : 0; ?>" />
		<input type="hidden" name="defaultvalue[<?php echo $id; ?>]" id="defaultvalue<?php echo $id; ?>" value="<?php echo ($field && is_string($field->defaultvalue)) ? $field->defaultvalue : 0 ?>" />
		<?php

	}

	private static $fieldscript;

	static function setField($id, $field, $html, $name)
	{
		if (!$field)
		{
			static $script;
			if (!isset(self::$fieldscript))
			{
				self::$fieldscript = "";
			}
			self::$fieldscript .= "rsvpFieldTypes['" . strtolower($name) . "']=" . json_encode($html) . ";\n";
			return "";
		}
		else
		{
			return $html;
		}

	}

	static function getFieldScript()
	{
		$document = JFactory::getDocument();
		$document->addScriptDeclaration(self::$fieldscript);

	}

	static function formonly($id, $field, $default = 0)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$style = $params->get("standardvisibility",1)?'' : 'style="display:none"';
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel" <?php echo $style;?> ><?php echo JText::_("RSVP_IN_FORM_ONLY"); ?></div>
		<div class="rsvpinputs  radio btn-group" <?php echo $style;?> >
			<label for="formonly1<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio"  name="formonly[<?php echo $id; ?>]"  id="formonly1<?php echo $id; ?>" value="1" <?php
		if ($field && $field->formonly == 1)
		{
			echo 'checked="checked"';
		}
		if (!$field && $default)
		{
			echo 'checked="checked"';
		}		
		?> />
			</label>
			<label for="formonly0<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio"  name="formonly[<?php echo $id; ?>]" id="formonly0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->formonly == 0)
		   {
			   echo 'checked="checked"';
		   }
		  if (!$field && !$default)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function peruser($id, $field)
	{
		$fieldname = "peruser[$id]";
		$value = $field ? $field->peruser : 0;
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><?php echo JText::_("RSVP_PER_USER"); ?></div>
		<div class="rsvpinputs">
			<?php
			$options = array();
			$options[] = JHtml::_('select.option', 0, JText::_("RSVP_PRIMARY_REGISTRATION"));
			$options[] = JHtml::_('select.option', 1, JText::_("RSVP_PRIMARY_REGISTRATION_AND_GUESTS"));
			$options[] = JHtml::_('select.option', 2, JText::_("RSVP_PRIMARY_GUESTS_ONLY"));

			// build the html select list
			echo JHtml::_('select.genericlist', $options, $fieldname, 'class="inputbox" size="1"', 'value', 'text', $value);
			?>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function showinform($id, $field)
	{
		$params = JComponentHelper::getParams("com_rsvppro");		
		$style = $params->get("standardvisibility",1)?'' : 'style="display:none"';
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel" <?php echo $style;?> ><?php echo JText::_("RSVP_SHOW_IN_FORM"); ?></div>
		<div class="rsvpinputs  radio btn-group" <?php echo $style;?> >
			<label for="showinform1<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio"  name="showinform[<?php echo $id; ?>]"  id="showinform1<?php echo $id; ?>" value="1" <?php
		if ($field && $field->showinform == 1)
		{
			echo 'checked="checked"';
		}
		if (!$field)
		{
			echo 'checked="checked"';
		}
		?> />
			</label>
			<label for="showinform0<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio"  name="showinform[<?php echo $id; ?>]" id="showinform0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->showinform == 0)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function showinlist($id, $field, $default = 1)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$style = $params->get("standardvisibility",1)?'' : 'style="display:none"';
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel" <?php echo $style;?> ><?php echo JText::_("RSVP_SHOW_IN_LIST"); ?></div>
		<div class="rsvpinputs  radio btn-group" <?php echo $style;?> >
			<label for="showinlist1<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio"  name="showinlist[<?php echo $id; ?>]"  id="showinlist1<?php echo $id; ?>" value="1" <?php
		if ($field && $field->showinlist == 1)
		{
			echo 'checked="checked"';
		}
		if (!$field && $default)
		{
			echo 'checked="checked"';
		}
		?> />
			</label>
			<label for="showinlist0<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio"  name="showinlist[<?php echo $id; ?>]" id="showinlist0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->showinlist == 0)
		   {
			   echo 'checked="checked"';
		   }
		   if (!$field && !$default)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function showindetail($id, $field, $default = 1)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$style = $params->get("standardvisibility",1)?'' : 'style="display:none"';
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel" <?php echo $style;?> ><?php echo JText::_("RSVP_SHOW_IN_DETAIL"); ?></div>
		<div class="rsvpinputs  radio btn-group" <?php echo $style;?> >
			<label for="showindetail1<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio"  name="showindetail[<?php echo $id; ?>]"  id="showindetail1<?php echo $id; ?>" value="1" <?php
		if ($field && $field->showindetail == 1)
		{
			echo 'checked="checked"';
		}
		if (!$field && $default)
		{
			echo 'checked="checked"';
		}		
		?> />
			</label>
			<label for="showindetail0<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio"  name="showindetail[<?php echo $id; ?>]" id="showindetail0<?php echo $id; ?>" value="0" <?php
		   if ($field && $field->showindetail == 0)
		   {
			   echo 'checked="checked"';
		   }
		  if (!$field && !$default)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	static function applicableCategories($fieldids, $fieldidc, $id, $categories)
	{
		return "";
		?>
		<div class="rsvpnotranslate">
		<div class="rsvplabel"><?php echo JText::_("RSVP_APPLICABLE_CATEGORIES"); ?></div>
		<div class="rsvpinputs">
			<?php
			static $donescript;
			if (!isset($donescript))
			{
				$doc = JFactory::getDocument();
				$script = <<<SCRIPT
		   		function allselections(id) {
		   			var e = document.getElementById(id);
		   			e.disabled = true;
		   			var i = 0;
		   			var n = e.options.length;
		   			for (i = 0; i < n; i++) {
		   				e.options[i].disabled = true;
		   				e.options[i].selected = true;
		   			}
		   		}
		   		function enableselections(id) {
		   			var e = document.getElementById(id);
		   			e.disabled = false;
		   			var i = 0;
		   			var n = e.options.length;
		   			for (i = 0; i < n; i++) {
		   				e.options[i].disabled = false;
		   			}
		   		}
SCRIPT;
				$doc->addScriptDeclaration($script);
				$donescript = 1;
			}

			$fieldidcStripped = str_replace(array("[", "]"), "", $fieldidc);

			JLoader::register('JEventsCategory', JEV_ADMINPATH . "/libraries/categoryClass.php");

			$cattree = JEventsCategory::categoriesTree();
			$categorylist = JHtml::_('select.genericlist', $cattree, $fieldidc . '[]', 'multiple="multiple" size="15"', 'value', 'text', explode("|", $categories));
			?>
			<fieldset class="adminform">
				<legend><?php echo JText::_('RSVP_APPLICABLE_CATEGORIES'); ?></legend>
				<table class="admintable" cellspacing="1">
					<tr>
						<td class="key">
							<?php echo JText::_('RSVP_CATEGORIES'); ?>:
						</td>
						<td class="rsvpinputs  radio btn-group">
							<?php
							if ($categories == 'all' || $categories == '')
							{
								?>
								<label for="categories-all<?php echo $fieldidc; ?>" class="btn radio">
									<input id="categories-all<?php echo $fieldidc; ?>" type="radio"  name="<?php echo $fieldids; ?>" value="all" onclick="allselections('<?php echo $fieldidcStripped; ?>');" checked="checked" />
									<?php echo JText::_('RSVP_ALL'); ?>
								</label>
								<label for="categories-select<?php echo $fieldidc; ?>" class="btn radio">
									<input id="categories-select<?php echo $fieldidc; ?>" type="radio"  name="<?php echo $fieldids; ?>" value="select" onclick="enableselections('<?php echo $fieldidcStripped; ?>');" />
									<?php echo JText::_('RSVP_SELECT_FROM_LIST'); ?>
								</label>
								<?php
							}
							else
							{
								?>
								<label for="categories-all<?php echo $fieldidc; ?>" class="btn radio">
									<input id="categories-all<?php echo $fieldidc; ?>" type="radio"  name="<?php echo $fieldids; ?>" value="all" onclick="allselections('<?php echo $fieldidcStripped; ?>');" />
									<?php echo JText::_('RSVP_ALL'); ?>
								</label>
								<label for="categories-select<?php echo $fieldidc; ?>" class="btn radio">
									<input id="categories-select<?php echo $fieldidc; ?>" type="radio"  name="<?php echo $fieldids; ?>" value="select" onclick="enableselections('<?php echo $fieldidcStripped; ?>');" checked="checked" />
									<?php echo JText::_('RSVP_SELECT_FROM_LIST'); ?>
								</label>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td class="paramlist_key" width="40%"><span class="editlinktip">
								<label for="categories<?php echo $fieldidc; ?>" class="categories-lbl"><?php echo JText::_('RSVP_CATEGORY_SELECTION'); ?></label>
						</td>
						<td>
							<?php echo $categorylist; ?>
						</td>
					</tr>
				</table>
				<?php
				if ($categories == 'all' || $categories == '')
				{
					?>
					<script type="text/javascript">allselections('<?php echo $fieldidcStripped; ?>');</script>
				<?php } ?>
			</fieldset>
		</div>
		</div>
		<?php

	}

	static function phpMoneyFormat($amount)
	{
		static $params;
		if (!isset($params))
		{
			// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
			$registry = JRegistry::getInstance("jevents");
			$rsvpdata = $registry->get("rsvpdata");
			$eventrepeat = $registry->get("event");

			$params = JComponentHelper::getParams("com_rsvppro");
			if (is_numeric($rsvpdata->template))
			{
				$db = JFactory::getDBO();
				$query = 'SELECT w.* FROM #__jev_rsvp_templates AS w WHERE w.id = ' . (int) $rsvpdata->template;
				$db->setQuery($query);
				$data = $db->loadObject();
				if ($data)
				{
					$data = json_decode($data->params);
					foreach (get_object_vars($data) as $k => $v)
					{
						if (is_object($v) || is_array($v))
							continue;
						$params->set($k, $v);
					}
				}
			}
		}

		$digits = $params->get("CurrencyDigits");
		$symbol = $params->get("CurrencySymbol");
		$onLeft = (strcmp($params->get("CurrencyPlacement"), 'left') == 0);
		$separator = $params->get("CurrencySeparator");
		$decimal = $params->get("CurrencyDecimal");
		
		// People keep adding symbols! lets remove them.
		$amount = str_replace($symbol, '', $amount);

		$formattedText = '';

		// negative amount
		if ($amount < 0)
			$formattedText .= '-';

		// currency symbol on the left
		if ($onLeft)
			$formattedText .= $symbol;

		// format with correct number of digits and separator
		if ($digits >= 0)
		{
			// Floating point errors are a MAJOR PAIN e.g. amount 156.80 -> 156.81 with this code
			//$factor = pow(10, $digits);
			//$amount = ceil(abs($amount)*$factor)/$factor;
			$amount = RsvpHelper::ceil_dec($amount, $digits, ".");
			if ($amount==0){
				$formattedText = '';
				if ($onLeft)
					$formattedText .= $symbol;
			}
			/*
			 * test cases
			 */
			/*
			$test = 123.456789;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = 123.00004;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = 123.9999999999;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = 123.000000000001;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = -123.456789;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = -123.00004;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = -123.9999999999;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");

			$test = -123.00000000001;
			$test2 = RsvpHelper::ceil_dec($test, $digits, ".");
			*/
			$formattedText .= number_format($amount, $digits, $decimal, $separator);
		}
		else
		{
			$amount = ceil(abs($amount));
			$formattedText = $amount;
		}

		// currency symbols on the right
		if (!$onLeft)
			$formattedText .= $symbol;


		return $formattedText;

	}

	// see http://php.net/manual/en/function.ceil.php#108370 - IMPROVED THOUGH!
	static public function ceil_dec($number, $precision, $separator = ".")
	{
		// If the whole number is less than .5 under the precision then return zero 
		if (abs($number)*pow(10, $precision)<0.5){
			return 0;
		}
		
		$numberpart = explode($separator, $number);
		if (count($numberpart)==1){
			return intval($number);
		}
		// $numberpart[1] could have a leading zero so we make it a LOT bigger and then remove this at the end
		$leadingZero = false;
		if (substr($numberpart[1], 0, 1) === "0") {
			$numberpart[1] = "5" . substr($numberpart[1],1);
			$leadingZero = true;
		}
		$numberpart[1] = substr_replace($numberpart[1], $separator, $precision, 0);
		if ($numberpart[0] >= 0)
		{
			$numberpart[1] = ceil($numberpart[1]);
			if (strlen($numberpart[1]) > $precision){
				$numberpart[0] +=1;
				$numberpart[1] = substr($numberpart[1],1);
			}
		}
		else
		{
			$numberpart[1] = floor($numberpart[1]);
			if (strlen($numberpart[1]) > $precision){
				$numberpart[0] -=1;
				$numberpart[1] = substr($numberpart[1],1);
			}

		}

		if ($leadingZero){
			$lead = intval(substr($numberpart[1], 0, 1)) - 5;
			$numberpart[1] = ((string) $lead) . substr($numberpart[1],1);
		}
		$ceil_number = array($numberpart[0], $numberpart[1]);
		return implode($separator, $ceil_number);

	}

	static function phpNewMoneyFormat($amount, $template=false) {
		if ($template && isset($template->Currency)){
			$digits 	= $template->CurrencyDigits;
			$symbol 	= $template->CurrencySymbol;
			$onLeft 	= (strcmp($template->CurrencyPlacement,'left') == 0);
			$separator 	= $template->CurrencySeparator;
			$decimal	= $template->CurrencyDecimal;
		}
		else {
			$eSessParams = JComponentHelper::getParams('com_rsvppro');

			$digits 	= $eSessParams->get("CurrencyDigits");
			$symbol 	= $eSessParams->get("CurrencySymbol");
			$onLeft 	= (strcmp($eSessParams->get("CurrencyPlacement"),'left') == 0);
			$separator 	= $eSessParams->get("CurrencySeparator");
			$decimal	= $eSessParams->get("CurrencyDecimal");
		}
		$formattedText  = '';

		// negative amount
		if ( $amount < 0 ) $formattedText .= '-';

		// currency symbol on the left
		if ( $onLeft )     $formattedText .= $symbol;

		// format with correct number of digits and separator
		if ( $digits > 0 ) {
			$amount = RsvpHelper::ceil_dec($amount, $digits, ".");
			$formattedText .= number_format( abs($amount), $digits, $decimal, $separator );
		} else {
			$formattedText = abs( $amount );
		}

		// currency symbols on the right
		if ( !$onLeft )    $formattedText .= $symbol;


		return $formattedText;
	}
	
	static function jsMoneyFormat()
	{

		static $loaded;
		if (isset($loaded))
			return;

		$loaded = true;
		$params = JComponentHelper::getParams("com_rsvppro");

		$digits = $params->get("CurrencyDigits", 2);
		$symbol = $params->get("CurrencySymbol", "$");
		$onLeft = (strcmp($params->get("CurrencyPlacement"), 'left') == 0);
		$separator = $params->get("CurrencySeparator", ",");
		$decimal = $params->get("CurrencyDecimal",".");

		$jsCode = "
			function moneyFormat(amount) {
				// ensure numerical input
				amount = parseFloat(amount);

				// Get the ceiling amount to match the payment plugin process
				var factor = Math.pow(10, " . $digits . ");
				amount = Math.ceil(amount * factor)/factor;

				// format to the correct number of digits
				// @todo prototype needs to implement toFixed() for browsers that don't support this.
				amount = amount.toFixed(" . $digits . ");
				
				// split into whole/partial for thousands separator
				var dollars = amount.split('.')[0];
				var cents	= amount.split('.')[1];
	
				// apply separator between every three digits
				var rgx = /(\d+)(\d{3})/;
				while (rgx.test(dollars)) {
					dollars = dollars.replace(rgx, '\$1' + '$separator' + '\$2');
				}
				
				";
		if ($onLeft)
		{
			if ($digits > 0)
			{
				$jsCode .= "return '$symbol' + dollars + '$decimal' + cents;\n";
			}
			else
			{
				$jsCode .= "return '$symbol' + dollars;\n";
			}
		}
		else
		{
			if ($digits > 0)
			{
				$jsCode .= "return dollars + '$decimal' + cents + '$symbol';\n";
			}
			else
			{
				$jsCode .= "return dollars + '$symbol';\n";
			}
		}
		$jsCode .= "
			}";

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($jsCode);

	}

	public static function 	translate($string){
		// Is there a translation - simple translation
		$translation = JText::_($string);
		if($translation != $string) {
			return $translation;
		}
		// more name spaced!
		$totranslate = str_replace(" ","_", trim($string) );
		$totranslate = "RSVP_FIELD_" . (string) preg_replace('/[^A-Z0-9_]/i', '', $totranslate);
		$translation = JText::_($totranslate);
		if($translation != $totranslate) {
			return $translation;
		}
		return $string;
	}

	static function conditional($id, $field)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		if (!$params->get("allowconditional",1)){
			return ;
		}

		if ($field)
		{
			try {
				$params = json_decode($field->params);
			}
			catch (Exception $e) {
				$params = array();
			}
		}
		if (isset($params->cf))
		{
			$cf = $params->cf;
		}
		else
		{
			$cf = "";
		}
		if (isset($params->cfvfv))
		{
			$cfvfv = $params->cfvfv;
		}
		else
		{
			$cfvfv = 1;
		}
		$params = JComponentHelper::getParams("com_rsvppro");
		$style = 'style="display:none"';

		?>
		<div class="rsvpnotranslate rsvpconditional" <?php echo $style;?>>
		<div class="rsvplabel"   ><?php echo JText::_("RSVP_CONDITIONAL"); ?></div>
		<div class="rsvpinputs"   >
			<div class="rsvplabel" ><label for="cf<?php echo $id; ?>"><?php echo JText::_("RSVP_CONDITION_FIELD"); ?></label></div>
			<div class="rsvpinputs">
				<select name="params[<?php echo $id; ?>][cf]" id="cf<?php echo $id; ?>" class="cf" onchange="conditionalEditorPlugin.updateSelection('cf<?php echo $id; ?>');"	>
				<option value=""><?php echo JText::_("RSVP_NOT_CONDITIONAL"); ?></option>
				</select>
			</div>
			<div style="clear:left;" ></div>
			<div class="rsvpconditionalselector"  <?php echo $style;?>>
				<div class="rsvplabel" ><label for="cfvfv<?php echo $id; ?>"><?php echo JText::_("RSVP_CONDITION_VISIBLE_FIELDVALUE"); ?></label></div>
				<div class="rsvpinputs">
					<select name="params[<?php echo $id; ?>][cfvfv]" id="cfvfv<?php echo $id; ?>" class="cfvfv" >
						<option value="1" <?php echo $cfvfv?"selected='selected'":"";?>><?php echo JText::_("RSVP_YES"); ?></option>
						<option value="0" <?php echo !$cfvfv?"selected='selected'":"";?>><?php echo JText::_("RSVP_NO"); ?></option>
					</select>
				</div>
			</div>

		</div>
		<?php
		static $scripts = array();
		$script = 'jQuery(document).ready(function(){if (conditionalEditorPlugin) conditionalEditorPlugin.update("' . $id . '", "' . $cf . '");});';
		if (!in_array($script, $scripts)){
			$doc = JFactory::getDocument();
			$doc->addScriptDeclaration($script);
			$scripts[]  = $script;
		}
		?>
		<div class="rsvpclear"></div>
		</div>
		<?php

	}

	// This method overrides global component parameters with values from template params
	static public function getTemplateParams($rsvpdata){
		static $paramsarray;
		if (!isset($paramsarray)){
			$paramsarray = array();
		}
		if (!isset($paramsarray[$rsvpdata->id])){
			$comparams = JComponentHelper::getParams("com_rsvppro");

			if (isset($rsvpdata->template) && is_numeric($rsvpdata->template))
			{
				$db = JFactory::getDBO();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));
				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
				}
				else
				{
					$templateParams = new stdClass();
				}
				foreach (get_object_vars($templateParams) as $k => $v){
					$comparams->set($k, $v);
				}
			}
			$paramsarray[$rsvpdata->id] = $comparams;
		}
		return $paramsarray[$rsvpdata->id];
	}

	/**
	 * Converts a double colon seperated string or 2 separate strings to a string ready for bootstrap tooltips
	 *
	 * @param   string  $title      The title of the tooltip (or combined '::' separated string).
	 * @param   string  $content    The content to tooltip.
	 * @param   int     $translate  If true will pass texts through JText.
	 * @param   int     $escape     If true will pass texts through htmlspecialchars.
	 *
	 * @return  string  The tooltip string
	 *
	 * @since   3.1.17
	 */
	public static function tooltipText($title = '', $content = '', $translate = 1, $escape = 1)
	{
		// Return empty in no title or content is given.
		if ($title == '' && $content == '')
		{
			return '';
		}

		// Split title into title and content if the title contains '::' (old Mootools format).
		if ($content == '' && !(strpos($title, '::') === false))
		{
			list($title, $content) = explode('::', $title, 2);
		}

		// Pass texts through the JText.
		if ($translate)
		{
			$title = JText::_($title);
			$content = JText::_($content);
		}

		// Escape the texts.
		if ($escape)
		{
			$title = str_replace('"', '&quot;', $title);
			$content = str_replace('"', '&quot;', $content);
		}

		// Return only the content if no title is given.
		if ($title == '')
		{
			return $content;
		}

		if (version_compare(JVERSION, "3.2.0", 'ge')){

			// Return only the title if title and text are the same.
			if ($title == $content)
			{
				return '<strong>' . $title . '</strong>';
			}

			// Return the formated sting combining the title and  content.
			if ($content != '')
			{
				return '<strong>' . $title . '</strong><br />' . $content;
			}
		}
		else {
			return $title."::".$content;
		}


		// Return only the title.
		return $title;
	}

	
}
