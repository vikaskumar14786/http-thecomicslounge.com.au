<?php
	// No direct access
	defined('_JEXEC') or die; 
?>

<div class ="<?php echo $params->get('moduleclass_sfx');?>">

<div class="ForHireFormHead"><br />I WOULD LIKE TO HIRE A COMEDIAN</div>	
	<br />	
	<div class="ForHireFormTxt">	
		<form method="post" name="hire_form">
			<fieldset>
				<label for="name">NAME</label>
				<input type="text" id="name" style="width:65%;" />
				
				<label for="name">COMPANY</label>
				<input type="text" id="company" style="width:65%;" />
				
				<label for="name">TELEPHONE</label>
				<input type="text" id="telephone" style="width:65%;" />
				
				<label for="email">E-MAIL</label>
				<input type="text" id="email" style="width:65%;" />
				
				<label for="name">FUNCTION TYPE</label>
				<input type="text" id="type" style="width:65%;" />
				
				<label for="name">FUNCTION DATE</label>
				<?php  echo JHTML::calendar(date("d-m-Y"),'mycalendar', 'date', '%d-%m-%Y',array('size'=>'6','maxlength'=>'8','class'=>' validate[\'required\']',)); ?>
				
				
  
				<br />
				<button type="submit" class="clButton">SEND ENQUIRY</button>
				
			</fieldset>
		
			<input type="hidden" name="check" value="1" />
		</form>
	</div>
</div>