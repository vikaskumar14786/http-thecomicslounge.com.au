jQuery.noConflict();


	function newsletterFormSubmit()
	{ 
		
		  jQuery.validator.addMethod("start_with_alphabet", function(value, element) {
			return (this.optional(element) || /^[A-Za-z][A-Za-z0-9 .]*$/.test(value) );
			});
		 
		  jQuery("#subscribeForm").validate({
		  rules:
			   {
				  name:{
				start_with_alphabet:true,
				required:true,
				minlength:2,
				maxlength:32
				  },
				  email:{
				  required:true,
				  email:true,
				  minlength:2,
				  maxlength:32}
					},
			  messages:
			  {
			  }
		});

		var isTrue  = jQuery('#subscribeForm').valid();		
		if(isTrue==true)
		{
			//ajaxAddNewsletter();
			//send_details to http://www.myguestlist.com.au/mgl/formreceiver.php?api	
			ajaxSendDtl();
			ajaxAddNewsletter();
			jQuery('#subscribeForm').submit(function() {			 
			  return false;
			});			
		}
}


    jQuery(function(){ 
		    // find all the input elements with title attributes
				jQuery("input[title!='']").hint();
			});


function ajaxAddNewsletter() {

var name = jQuery('#name1').val();
var email = jQuery('#email').val();
if(name && email)
	{
		jQuery.ajax({
		  type: "POST",
		  url: 'index.php',
		  cache: false,
		  data: "option=com_ccnewsletter&task=addSubscriber&ajaxRequest=1&name="+name+"&email="+email+"&tmpl=component",
		  success: function(res) {	
				jQuery('#name1').val('');
				jQuery('#email').val('');
			   jQuery('#ajaxResp').html(res);			  
			  return false;			 	
		  }
		});
	}
	else
	{
		alert('Please enter all required fields');
		return false;
	}

}

function ajaxSendDtl(){


var name = jQuery('#name1').val();
var email = jQuery('#email').val();
if(name && email)
{
var name_array = name.split(' ');
var first_name = name_array[0];
var last_name='';
if(name_array[1]){
jQuery.each(name_array , function(i, val) {
	if(name_array [(i+1)]){
  		last_name=last_name+name_array [(i+1)]+' ';
	}
});

}

var url_str="http://www.myguestlist.com.au/mgl/formreceiver.php?api=&formID=mfbb4839cb99d&PatronName="+name+"&PatronSurname="+name+"&PatronEmail="+email;

		jQuery.ajax({
		  type: "POST",
		  url:"http://www.myguestlist.com.au/mgl/formreceiver.php?api=&formID=mfbb4839cb99d&PatronName="+first_name+"&PatronSurname="+last_name+"&PatronEmail="+email,		  
		  success: function(res) {
				alert(res);	
					 	
		  }
		});

}
}
