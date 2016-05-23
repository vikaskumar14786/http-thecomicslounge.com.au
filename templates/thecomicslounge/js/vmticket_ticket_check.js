 function clearSession(url){	
		jQuery.ajax({
		type: "POST",
			url: 'index.php',
			cache: false,
			data: "option=com_vmeticket&task=clearSession&tmpl=component",
			success: function(res){
					window.location=url;
			return true;			 	
		},
		});
	
	}