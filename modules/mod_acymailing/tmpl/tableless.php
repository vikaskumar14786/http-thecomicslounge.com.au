<?php
/**
 * @package	AcyMailing for Joomla!
 * @version	4.9.1
 * @author	acyba.com
 * @copyright	(C) 2009-2015 ACYBA S.A.R.L. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?>
<?php
	$style = array();
	if($params->get('effect','normal') == 'mootools-slide'){
		if(!empty($mootoolsIntro)) echo '<p class="acymailing_mootoolsintro">'.$mootoolsIntro.'</p>'; ?>
		
			<p><a class="acymailing_togglemodule" id="acymailing_togglemodule_<?php echo $formName; ?>" href="#subscribe"><?php echo $mootoolsButton ?></a></p>
	<?php
	}
	if($params->get('textalign','none') != 'none') $style[] .= 'text-align:'.$params->get('textalign');
	$styleString = empty($style) ? '' : 'style="'.implode(';',$style).'"';
	?>

		<form id="<?php echo $formName; ?>" action="<?php echo JRoute::_('index.php'); ?>" onsubmit="return submitacymailingform('optin','<?php echo $formName;?>')" method="post" name="<?php echo $formName ?>" <?php if(!empty($fieldsClass->formoption)) echo $fieldsClass->formoption; ?> >

			<?php if(!empty($introText)) echo '<div class="acymailing_introtext">'.$introText.'</div>';

			$listContent = '';
			if($params->get('dropdown',0)){
				$listContent .= '<select name="subscription[1]">';
				foreach($visibleListsArray as $myListId){
					$listContent .= '<option value="'.$myListId.'">'.$allLists[$myListId]->name.'</option>';
				}
				$listContent .= '</select>';
			} else{
				$listContent .= '<div class="acymailing_lists">';
				foreach($visibleListsArray as $myListId){
					$check = in_array($myListId,$checkedListsArray) ? 'checked="checked"' : '';

					if($params->get('checkmode',0) == '0' AND !empty($identifiedUser->email)){
						if(empty($allLists[$myListId]->status)){$check = '';}
						else{
							$check = $allLists[$myListId]->status == '-1' ? '' : 'checked="checked"';
						}
					}
					$listContent .= '
					<p>
						<label for="acylist_'.$myListId.'">
						<input type="checkbox" class="acymailing_checkbox" name="subscription[]" id="acylist_'.$myListId.'" '.$check.' value="'.$myListId.'"/>';
						$joomItem = $params->get('itemid',0);
						if(empty($joomItem)) $joomItem = $config->get('itemid',0);
						$addItem = empty($joomItem) ? '' : '&Itemid='.$joomItem;
						$archivelink = acymailing_completeLink('archive&listid='.$allLists[$myListId]->listid.'-'.$allLists[$myListId]->alias.$addItem);
						if($params->get('overlay',0)){
							if(!$params->get('link',1) OR !$allLists[$myListId]->visible) $archivelink = '';
							$listContent .= acymailing_tooltip($allLists[$myListId]->description,$allLists[$myListId]->name,'',$allLists[$myListId]->name,$archivelink);
						}else{
							if($params->get('link',1) AND $allLists[$myListId]->visible){
								$listContent .= '<a href="'.$archivelink.'" alt="'.$allLists[$myListId]->alias.'"'.((JRequest::getCmd('tmpl') == 'component') ? 'target="_blank"' : '').' >';
							}
							$listContent .= $allLists[$myListId]->name;
							if($params->get('link',1) AND $allLists[$myListId]->visible){
								$listContent .= '</a>';
							}
						}
						$listContent .= '
						</label>
					</p>';
				 }
				$listContent .= '</div>';
			}

			if(!empty($visibleListsArray) && $listPosition == 'before') echo $listContent; ?> 
			<div class="newsLetterBox_Lft">
                <p class="sm_txt">NEWSLETTER</p>
                <p class="lg_txt">SIGNUP!</p>
            </div>
			<div class="newsLetterBox_Mid">
			
                    <div class="subs_LftBox">
			
					<?php
					$tmpCatId = array();
					$tmpCatTag = array();
					foreach($fieldsToDisplay as $oneField){
						if(empty($extraFields[$oneField])) echo '<p class="onefield fieldacy'.$oneField.'" id="field_'.$oneField.'_'.$formName.'">';
						if($oneField == 'name' AND empty($extraFields[$oneField])){
							if($displayOutside) echo '<label for="user_name_'.$formName.'" class="acy_requiredField">'.$nameCaption.'</label>'; ?>
							<span class="acyfield_<?php echo $oneField. (!$displayOutside? ' acy_requiredField':''); ?>"><input id="user_name_<?php echo $formName; ?>" <?php if(!empty($identifiedUser->userid)) echo 'disabled="disabled" '; if(!$displayOutside){ ?> onfocus="if(this.value == '<?php echo $nameCaption;?>') this.value = '';" onblur="if(this.value=='') this.value='<?php echo $nameCaption?>';"<?php } ?> class="inputbox" type="text" name="user[name]" style="width:<?php echo $fieldsize; ?>" value="<?php if(!empty($identifiedUser->userid)) echo $identifiedUser->name; elseif(!$displayOutside) echo $nameCaption; ?>" title="<?php echo $nameCaption;?>"/></span>
							<?php
						}elseif($oneField == 'email' AND empty($extraFields[$oneField])){
							if($displayOutside) echo '<label for="user_email_'.$formName.'" class="acy_requiredField">'.$emailCaption.'</label>'; ?>
							<span class="acyfield_<?php echo $oneField. (!$displayOutside? ' acy_requiredField':''); ?>"><input id="user_email_<?php echo $formName; ?>" <?php if(!empty($identifiedUser->userid)) echo 'disabled="disabled" '; if(!$displayOutside){ ?> onfocus="if(this.value == '<?php echo $emailCaption;?>') this.value = '';" onblur="if(this.value=='') this.value='<?php echo $emailCaption?>';"<?php } ?> class="inputbox" type="text" name="user[email]" style="width:<?php echo $fieldsize; ?>" value="<?php if(!empty($identifiedUser->userid)) echo $identifiedUser->email; elseif(!$displayOutside) echo $emailCaption; ?>" title="<?php echo $emailCaption;?>" /></span>
							<?php
						}elseif($oneField == 'html' AND empty($extraFields[$oneField])){
							echo '<label>'.JText::_('RECEIVE').'</label>';
							echo '<span class="acyfield_'.$oneField.'">'.JHTML::_('select.booleanlist', "user[html]" ,'title="'.JText::_('RECEIVE').'"',isset($identifiedUser->html) ? $identifiedUser->html : 1,JText::_('HTML'),JText::_('JOOMEXT_TEXT'),'user_html_'.$formName).'</span>';
						}elseif(!empty($extraFields[$oneField])){
							if($extraFields[$oneField]->type == 'category'){
								if(empty($extraFields[$oneField]->fieldcat) && !empty($tmpCatId)){
									while(!empty($tmpCatId)){
										echo '</'.end($tmpCatTag).'>';
										array_pop($tmpCatId);
										array_pop($tmpCatTag);
									}
								}
								$tmpCatId[] = $extraFields[$oneField]->fieldid;
								$tmpCatTag[] = $extraFields[$oneField]->options['fieldcattag'];
								echo '<'.end($tmpCatTag).' class="fieldCategory fieldacy'.$extraFields[$oneField]->namekey.' '.$extraFields[$oneField]->options['fieldcatclass'].'">';
								if(end($tmpCatTag) == 'fieldset') echo '<legend>'.$extraFields[$oneField]->fieldname.'</legend>';
							}else{
								if(in_array($extraFields[$oneField]->fieldcat, $tmpCatId) || empty($extraFields[$oneField]->fieldcat)){
									while(!empty($tmpCatId) && $extraFields[$oneField]->fieldcat != end($tmpCatId)){
										echo '</'.end($tmpCatTag).'>';
										array_pop($tmpCatId);
										array_pop($tmpCatTag);
									}
								}
								echo '<p class="onefield fieldacy'.$oneField.'" id="field_'.$oneField.'_'.$formName.'">';
								if($displayOutside){
									if(!empty($extraFields[$oneField]->required)) $requireClass = 'class="acy_requiredField"';
									else $requireClass = "";
									 echo '<label '.((strpos($extraFields[$oneField]->type,'text') !== false) ? 'for="user_'.$oneField.'_'.$formName.'"' : '' ).' '.$requireClass.'>'.$fieldsClass->trans($extraFields[$oneField]->fieldname).'</label>';
								}
								$sizestyle = '';
								if(!empty($extraFields[$oneField]->options['size'])){
									$sizestyle = 'style="width:'.(is_numeric($extraFields[$oneField]->options['size']) ? ($extraFields[$oneField]->options['size'].'px') : $extraFields[$oneField]->options['size']).'"';
								}
								if(!empty($extraFields[$oneField]->required) && !$displayOutside) $requireClass = ' acy_requiredField';
								else $requireClass = "";
								?>
								<span class="acyfield_<?php echo $oneField.$requireClass; ?>">
								<?php if(!empty($identifiedUser->userid) AND in_array($oneField,array('name','email'))){ ?>
										<input id="user_<?php echo $oneField; ?>_<?php echo $formName; ?>" disabled="disabled" class="inputbox" type="text" name="user[<?php echo $oneField;?>]" <?php echo $sizestyle; ?> value="<?php echo @$identifiedUser->$oneField; ?>" title="<?php echo $oneField;?>"/>
								<?php }else{
										echo $fieldsClass->display($extraFields[$oneField],@$identifiedUser->$oneField,'user['.$oneField.']',!$displayOutside);
								}?>
								</span>
								</p>
								<?php
							}
						}
						if(empty($extraFields[$oneField])) echo '</p>';
					}
					if(!empty($extraFields)){
						$lastVal = end($tmpCatId);
						while(!empty($lastVal)){
							echo '</'.end($tmpCatTag).'>';
							array_pop($tmpCatId);
							array_pop($tmpCatTag);
							$lastVal = end($tmpCatId);
						}
					}

			

				

			?>
					
				</div>
				
				
				<div class="subs_RgtBox">
				
						<?php if($params->get('showsubscribe',true)){?>
						<input class="button subbutton btn btn-primary" type="submit" value="<?php $subtext = $params->get('subscribetextreg'); if(empty($identifiedUser->userid) OR empty($subtext)){ $subtext = $params->get('subscribetext',JText::_('SUBSCRIBECAPTION')); } echo $subtext;  ?>" name="Submit" onclick="try{ return submitacymailingform('optin','<?php echo $formName;?>'); }catch(err){alert('The form could not be submitted '+err);return false;}"/>
						<?php }if($params->get('showunsubscribe',false) AND (!$params->get('showsubscribe',true) OR empty($identifiedUser->userid) OR !empty($countUnsub)) ){?>
						<input class="button unsubbutton btn btn-inverse" type="button" value="<?php echo $params->get('unsubscribetext',JText::_('UNSUBSCRIBECAPTION')); ?>" name="Submit" onclick="return submitacymailingform('optout','<?php echo $formName;?>')"/>
						<?php } ?>
					
				</div>
			<?php
			if(!empty($fieldsClass->excludeValue)){
				$js = "\n"."acymailing['excludeValues".$formName."'] = Array();";
				foreach($fieldsClass->excludeValue as $namekey => $value){
					$js .= "\n"."acymailing['excludeValues".$formName."']['".$namekey."'] = '".$value."';";
				}
				$js .= "\n";
				$doc = JFactory::getDocument();
				if($params->get('includejs','header') == 'header'){
					$doc->addScriptDeclaration( $js );
				}else{
					echo "<script type=\"text/javascript\">
							<!--
							$js
							//-->
							</script>";
				}
			}
			if(!empty($postText)) echo '<div class="acymailing_finaltext">'.$postText.'</div>';
			$ajax = ($params->get('redirectmode') == '3') ? 1 : 0;?>
			<input type="hidden" name="ajax" value="<?php echo $ajax; ?>"/>
			<input type="hidden" name="acy_source" value="<?php echo 'module_'.$module->id ?>" />
			<input type="hidden" name="ctrl" value="sub"/>
			<input type="hidden" name="task" value="notask"/>
			<input type="hidden" name="redirect" value="<?php echo urlencode($redirectUrl); ?>"/>
			<input type="hidden" name="redirectunsub" value="<?php echo urlencode($redirectUrlUnsub); ?>"/>
			<input type="hidden" name="option" value="<?php echo ACYMAILING_COMPONENT ?>"/>
			<?php if(!empty($identifiedUser->userid)){ ?><input type="hidden" name="visiblelists" value="<?php echo $visibleLists;?>"/><?php } ?>
			<input type="hidden" name="hiddenlists" value="<?php echo $hiddenLists;?>"/>
			<input type="hidden" name="acyformname" value="<?php echo $formName; ?>" />
			<?php if(JRequest::getCmd('tmpl') == 'component'){ ?>
				<input type="hidden" name="tmpl" value="component" />
				<?php if($params->get('effect','normal') == 'mootools-box' AND !empty($redirectUrl)){ ?>
					<input type="hidden" name="closepop" value="1" />
				<?php } } ?>
			<?php $myItemId = $config->get('itemid',0); if(empty($myItemId)){ global $Itemid; $myItemId = $Itemid;} if(!empty($myItemId)){ ?><input type="hidden" name="Itemid" value="<?php echo $myItemId;?>"/><?php } ?>

		</form>
</div>
