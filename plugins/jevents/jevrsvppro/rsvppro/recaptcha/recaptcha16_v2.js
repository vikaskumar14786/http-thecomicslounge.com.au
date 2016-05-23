

function checkRecaptchav2(form){

	var requestObject = {};

//alert(jQuery("#g-recaptcha-response").val());
	requestObject.gresponseField = jQuery("#g-recaptcha-response").val();//grecaptcha.getResponse();
	if (requestObject.gresponseField==""){
		return false;
	}

	requestObject.error = false;
	
	var url = recaptchaurlroot + "json.recaptcha_v2.php";
	
	var success=0;

	var jSonRequest = jQuery.ajax({
			type : 'POST',
			dataType : 'json',
			url : url,
			async : false,
			data : {'json':JSON.stringify(requestObject)},
			contentType: "application/x-www-form-urlencoded; charset=utf-8",
			scriptCharset: "utf-8"
			})
		.done(function(json){
			if (json.error){
				try {
					grecaptcha.reset();
					eval(json.error);
					jQuery(".g-recaptcha").css("background-color","red");
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else if (json.success){
					success=1;
					if (json.secretcaptcha && jQuery("#secretcaptcha").length){
						jQuery("#secretcaptcha").val( json.secretcaptcha);
					}

					try {
						jQuery(".g-recaptcha").css("background-color","inherit");
					}
					catch (e){
						jQuery(".g-recaptcha").css("background-color","transparent");
					}

				}
		})
		.fail( function( jqxhr, textStatus, error){
		alert(textStatus + ", " + error);
		hasConflicts = true;
	});

	if (!success) {
		return false;
	}
	return true;
}