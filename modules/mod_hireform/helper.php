<?php
	// No direct access
	defined('_JEXEC') or die;
	
	class mod_HireFormHelper
	{
		
		function sendMail($params)
		{
			$input = new JInput;
			$name = $input->get('name', null);
			$company = $input->get('company', null);
			$telephone = $input->get('telephone', null);			
			$email = $input->get('email', null);
			$type = $input->get('type', null);
			$date = $input->get('date', null);
			if($name == null || $company == null || $telephone == null|| $email == null || $type == null || $date == null)
				return false;
			
			$message = "Name : ".$name."<br/> Email :".$email." <br />Company : ".$company." <br />Telephone : ".$telephone." <br />Type : ".$type." <br />Date : ".$date ;
			
			$mailer = JFactory::getMailer();
			$mailer->setSender('glen_greenwood@yahoo.com.au');
			$mailer->setSubject('Mail From Hire Comedian Form');
			$mailer->setBody($message);
			if($send != true)
			{
				echo "Error Sending Message:".$send->message;
				return false;
			}
			else
			{
				echo "Mail Sent";
				return true;
			}
			
		}
	}
	
	
	
	
?>