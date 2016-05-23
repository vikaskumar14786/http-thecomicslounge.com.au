

function checkRecaptcha(form){

	if (! form.recaptcha_challenge_field) {
		return true;
	}
	var requestObject = new Object();
	
	requestObject.challengeField =  form.recaptcha_challenge_field.value;
	requestObject.responseField =  form.recaptcha_response_field.value;
	requestObject.error = false;

	var checkurl = recaptchaurlroot + "json.recaptcha.php";
	
	var success=0;
	var jSonRequest = jQuery.ajax({
		type : 'GET',
		dataType : 'json',
		url : checkurl,
		'async':false,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (json.error){
			try {
				Recaptcha.reload();
				eval(json.error);
				document.getElementById("recaptcha_response_field").style.backgroundColor="red";
			}
			catch (e){
				alert('could not process error handler');
			}
		}
		else {
			if(json.result == "success"){
				success=1;
				if (json.secretcaptcha && jQuery("secretcaptcha").length){
					jQuery("secretcaptcha").val( json.secretcaptcha);
				}

				try {
					document.getElementById("recaptcha_response_field").style.backgroundColor="inherit";
				}
				catch (e){
					document.getElementById("recaptcha_response_field").style.backgroundColor="transparent";
				}

			}
		}
	})
	.fail( function( jqxhr, textStatus, error){
		alert('Something went wrong with recaptcha...')			
	});

	if (!success) {
		return false;
	}
	return true;
}