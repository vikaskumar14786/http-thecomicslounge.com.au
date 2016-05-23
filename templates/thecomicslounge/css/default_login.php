<?php defined('_JEXEC') or die('Restricted access');
$url = JURI::Root();
		$doc =& JFactory::getDocument();
		$doc->addScript('libraries/js/jquery.min.js');
		$doc->addScript('libraries/js/jquery.validate.js');
		$doc->addScript('/templates/comicslounge/js/signupvalidation.js');
 JHTML::_('behavior.modal'); 		
?>
         					
		<div class="maindiv login_area">
			<form action="<?php echo JRoute::_( 'index.php', true, $this->params->get('usesecure')); ?>" method="post" name="com_form_login" id="com_form_login">
		<div class="loginemail loginFloat">
			
			<div class="logInBoxForm logInMTop">
				<label for="username"><?php echo JText::_('EMAIL') ?></label>
				<input name="username" id="username" type="text"  alt="username"  />
			</div>
		</div>
				<div class="loginemail loginFloat">

				<div class="logInBoxForm logInMTop">
					<label for="passwd" class="pw"><?php echo JText::_('Password') ?></label>
					<input type="password" id="passwd" name="passwd"  size="18" alt="password" />
				</div>
		</div>
		<div class="loginemail loginBtn" >
			<div class="logInBoxForm buttonFloat">
				<input type="submit" name="Submit" class="button eventInfoButton" value="<?php echo JText::_('LOGIN') ?>" />
				<input type="hidden" name="option" value="com_user" />
				<input type="hidden" name="task" value="login" />
				<input type="hidden" name="return" value="<?php echo $this->return; ?>" />
						<?php echo JHTML::_( 'form.token' ); ?>
			</div>
		</div>	
		</form>
		</div>
		


