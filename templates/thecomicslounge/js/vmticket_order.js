
	function submitForm (task, id)
	{
		var i_task = document.getElementById('task');
		var i_id = document.getElementById('_id'); 

		i_task.value = task;
		i_id.value = id;

		document.adminForm.submit();
	}

	function showOrderItems (element, order_id) 
	{
		var items_div = document.getElementById('order_items_'+order_id);

		if (items_div.style.display == 'none') {
			//close items div
			var elements = new Array();
			
			if (document.getElementsByClassName) {
				elements = document.getElementsByClassName("table-items", document.body);
			} else {
				var i = 0;
				a = document.getElementsByTagName();
				while (element = a[i++]) {
					if (element.className == "table-items") {
						elements.push(element);
					}
				}
			}

			for(var i=0;i < elements.length;i++) {
				elements[i].style.display = "none";
			}

			//switch icon to + in all orders 
			elements = new Array();
			
			if (document.getElementsByClassName) {
				elements = document.getElementsByClassName("icon items", document.body);
			} else {
				var i = 0;
				a = document.getElementsByTagName();
				while (element = a[i++]) {
					if (element.className == "icon items") {
						elements.push(element);
					}
				}
			}

			for(var i=0;i < elements.length;i++) {
				elements[i].style.backgroundPosition = 'left top';
			}

			//display items of selected order and switch icon to - 
		  	element.getElementsByTagName('DIV')[0].style.backgroundPosition = 'right top';
			items_div.style.display = 'block';
			document.getElementById("open_order").value = order_id;
		} else {
		  	element.getElementsByTagName('DIV')[0].style.backgroundPosition = 'left top';
			items_div.style.display = 'none';
		}
	}

	function updateItem (item_id) 
	{
		var i_task = document.getElementById('task');
		var i_id = document.getElementById('_id');
		//var i_quant = document.getElementById('product_quantity');
		
		i_task.value = 'updateItemQuantity';
		i_id.value = item_id;

		document.adminForm.submit();
	}

	function deleteItem(item_id) 
	{
		var i_task = document.getElementById('task');
		var i_id = document.getElementById('order_item_id');
		
		i_task.value = 'deleteItem';
		i_id.value = item_id;

		document.adminForm.submit();
	}

	function changeItemStatus (item_id, order_id)
	{
		var i_task = document.getElementById('task');
		var i_id = document.getElementById('_id');
		var i_item_id = document.getElementById('order_item_id');

		i_task.value = 'changeItemStatus';
		i_id.value = order_id;
		i_item_id.value = item_id;

		document.adminForm.submit();
	}

	function printItems()
	{
		document.adminForm.action = "index2.php?option=com_vmeticket&view=orders&layout=print";
		document.adminForm.target = "blank";
		document.adminForm.submit();
		document.adminForm.action = "";
		document.adminForm.target = "";
	} 