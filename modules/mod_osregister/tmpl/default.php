<?php

defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.formvalidation');

echo $params->get('introtext');

?>

<link href="<?php echo JURI::base(true); ?>/modules/mod_osregister/tmpl/mod_osregister.css" rel="stylesheet" type="text/css" />

<script type="text/javascript">
<!--
	Window.onDomReady(function(){
		document.formvalidator.setHandler('passverify', function (value) { return ($('password').value == value); });
	});
// -->
</script>

<form action="<?php echo JRoute::_('index.php?option=com_users&task=registration.register'); ?>" method="post" id="josForm" name="josForm" class="form-validate">

	<div class="jp-register">
		<div class="label">
			<label id="namemsg" for="jform_name">
				<?php echo JText::_( 'Name' ); ?>:
			</label>
		</div>
		<div class="input">
			<input type="text" name="jform[name]" id="jform_name" class="inputbox required" maxlength="50" value="" /> *
		</div>
		<div class="label">
			<label id="usernamemsg" for="jform_username">
				<?php echo JText::_( 'Username' ); ?>:
			</label>
		</div>
		<div class="input">
			<input type="text" id="jform_username" name="jform[username]" class="inputbox required validate-username" maxlength="25" value="" /> *
		</div>
		<div class="label">
			<label id="pwmsg" for="jform_password1">
				<?php echo JText::_( 'Password' ); ?>:
		</div>
		<div class="input">
			<input class="inputbox required validate-password" type="password" id="jform_password1" name="jform[password1]" value="" autocomplete="off" /> *
		</div>
		<div class="label">
			<label id="pw2msg" for="jform_password2">
				<?php echo JText::_( 'Verify Password' ); ?>:
			</label>
		</div>
		<div class="input">
			<input class="inputbox required validate-password" type="password" id="jform_password2" name="jform[password2]" value="" /> *
		</div>
		<div class="label">
			<label id="emailmsg" for="jform_email1">
				<?php echo JText::_( 'Email' ); ?>:
			</label>
		</div>
		<div class="input">
			<input type="email" id="jform_email1" name="jform[email1]" class="inputbox required validate-email" maxlength="100" value="" /> *
		</div>
		<div class="label">
			<label id="emailmsg" for="jform_email2">
				<?php echo JText::_( 'Confirm Email' ); ?>:
			</label>
		</div>
		<div class="input">
			<input type="email" id="jform_email2" name="jform[email2]" class="inputbox required validate-email" maxlength="100" value="" /> *
		</div>

		<button class="button validate" type="submit"><?php echo JText::_('Register'); ?></button>
	</div>
	<input type="hidden" name="task" value="register_save" />
	<input type="hidden" name="option" value="com_users" />
	<input type="hidden" name="task" value="registration.register" />
	<?php echo JHtml::_('form.token');?>
</form>
