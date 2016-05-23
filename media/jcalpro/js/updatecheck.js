/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

JCalPro.onLoad(function(){
	var placeholder = JCalPro.id('jcalpro_extensionupdate'), pfx = 'COM_JCALPRO_EXTENSIONUPDATE_', placeholder;
	if (1 > placeholder.length) {
		return;
	}
	JCalPro.request({
		url: window.jcalproupdateurl
	,	onSuccess: function(response) {
			try {
				if ('object' === typeof response) {
					response = response[0].nodeValue;
				}
				var responseJSON = JCalPro.json_decode(response);
			}
			catch (err) {
				JCalPro.debug(response, err);
				JCalPro.setHtml(placeholder, Joomla.JText._(pfx + 'ERROR'));
				return;
			}
			var updateString = Joomla.JText._(pfx + 'UPTODATE'), hasUpdate = false, updateButton;
			if (responseJSON instanceof Array) {
				if (responseJSON.length >= 1) {
					JCalPro.each(responseJSON, function(el, idx) {
						if ('jcalpro' === el.name) {
							hasUpdate = true;
							updateString = Joomla.JText._(pfx + 'UPDATEFOUND').replace("%s", el.version);
							window.jcalproupdateid = el.update_id;
						}
					});
				}
			}
			else {
				JCalPro.debug(response, responseJSON);
				// An error occured
				updateString = Joomla.JText._(pfx + 'ERROR');
			}
			JCalPro.setHtml(placeholder, updateString);
			if (hasUpdate) {
				updateButton = JCalPro.getElement(placeholder, 'button');
				if (updateButton) {
					try {
						JCalPro.onClick(updateButton, function(ev){
							JCalPro.stopEvent(ev);
							var form = JCalPro.newElement('form', {
								method: 'post', action: window.jcalproupdateaction
							})
							, input = JCalPro.newElement('input', {
								type: 'hidden', name: 'cid[]', value: window.jcalproupdateid
							})
							, token = JCalPro.newElement('input', {
								type: 'hidden', name: window.jcalproupdatetoken, value: 1
							})
							;
							JCalPro.inject(input, form, 'top');
							JCalPro.inject(token, form, 'top');
							JCalPro.inject(form, placeholder, 'top');
							form.submit();
						});
					}
					catch (err) {
						JCalPro.debug(err);
						JCalPro.destroy(updateButton);
					}
				}
			}
		}
	,	onFailure: function(req) {
			// An error occured
			JCalPro.setHtml(placeholder, Joomla.JText._(pfx + 'ERROR'));
		},
	});
});
