function updateFromDate() 
	{
		var filter_date = document.getElementById('filter_date_from');
		var show_date = document.getElementById('show_date_from');
		var date = new Date(filter_date.value);

		show_date.value = date.print(message_str);
	}
	
	function updateToDate() 
	{
		var filter_date = document.getElementById('filter_date_to');
		var show_date = document.getElementById('show_date_to');
		var date = new Date(filter_date.value);

		show_date.value = date.print(message_str);
	}