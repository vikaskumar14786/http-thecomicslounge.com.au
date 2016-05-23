//Sign up form validation used in com_users/view=login/tmpl/default_login.php

jQuery.noConflict();
    jQuery(document).ready(function() {
	jQuery("#josForm").validate({
        	rules: {
			password: {
				required: true,
				minlength: 5
			},
			password2: {
				required: true,
				minlength: 5,
				equalTo: "#password"
			},
			email: {
				required: true,
				email: true
			},
			toc: {
			required: true	
				
			}
		},
		messages: {
			password: {
				required: "Please provide a password",
				minlength: "Your password must be at least 5 characters long"
			},
			password2: {
				required: "Please provide a password",
				minlength: "Your password must be at least 5 characters long",
				equalTo: "Please enter the same password as above"
			},
			email: "Please enter a valid email address"
	
		}
	});
jQuery("#com_form_login").validate({
		
		rules: {
			
			username: {
				required: true
			},
			passwd: {
				required: true
		       }		
		},
		messages: {
			username: "Please enter a valid email address",
			passwd: "Please enter  password"
	
		}
	});
});