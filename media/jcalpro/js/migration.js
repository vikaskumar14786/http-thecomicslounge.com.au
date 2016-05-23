/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

window.onbeforeunload = function() {
	return window.jcl_migration_text.not_finished;
};

(function() {
	// start drawing ui for migration before buttons
	var buttons, container, status, bar, pct, progress, pending;
	buttons   = JCalPro.id('jcl_install_elements');
	container = JCalPro.newElement('div', {id: 'jcl_migration_container'});
	status    = JCalPro.newElement('div', {id: 'jcl_migration_status'});
	errors    = JCalPro.newElement('div', {id: 'jcl_migration_errors'});
	bar       = JCalPro.newElement('div', {id: 'jcl_migration_progress_bar'});
	prog_text = JCalPro.newElement('div', {id: 'jcl_migration_progress_text'});
	progress  = JCalPro.newElement('div', {id: 'jcl_migration_progress'});
	pending   = [];
	// helper for errors
	var jcl_migrate_set_error = function(err) {
		var el = JCalPro.newElement('div');
		JCalPro.setText(el, err);
		JCalPro.inject(el, errors);
		JCalPro.setText(status, '');
		JCalPro.setStyles(progress, {'background':'red'});
	};
	// helper for progress bar
	var jcl_migrate_set_progress = function(cnt, tot) {
		var p = Math.min(100, Math.max(0, parseInt(((cnt / tot) * 100), 10))) + '%';
		JCalPro.setText(prog_text, cnt + ' / ' + tot + ' ( ' + p + ' )');
		JCalPro.setStyles(progress, {width: p});
	};
	// helper for batches
	var jcl_migrate_item = function(item, success) {
		var data = {
			url: 'index.php?option=com_jcalpro&task=install.migrateitem&format=json'
		,	requestType: 'json'
		,	data: item
		};
		if ('function' == typeof success) {
			data.onSuccess = success;
		}
		var req = JCalPro.request(data);
	};
	var jcl_migrate_batch = function(i) {
		var next = i + 1;
		if (pending[i]) {
			jcl_migrate_item(pending[i], function(responseJSON, responseText) {
				try {
					// handle errors
					if (responseJSON.errorText) {
						jcl_migrate_set_error(responseJSON.errorText);
					}
					else {
						JCalPro.setText(status, responseJSON.updateText);
					}
				}
				catch (err) {
					jcl_migrate_set_error(window.jcl_migration_text.bad_request + ' - ' + err);
				}
				jcl_migrate_set_progress(i, pending.length);
				jcl_migrate_batch(next);
			});
			return;
		}
		JCalPro.setText(status, window.jcl_migration_text.finished);
		jcl_migrate_set_progress(pending.length, pending.length);
		window.onbeforeunload = null;
	};
	// set initial styles on progress bar elements
	JCalPro.setStyles(bar, {
		'width': '100%'
	,	'height': '16px'
	,	'background-color': '#E9E9E9'
	,	'border': '1px solid #999'
	,	'position': 'relative'
	,	'overflow': 'hidden'
	,	'margin': '6px 4px'
	});
	JCalPro.setStyles(progress, {
		'width': '0%'
	,	'height': '16px'
	,	'background-color': 'blue'
	,	'position': 'absolute'
	,	'top': '0px'
	,	'left': '0px'
	});
	JCalPro.setStyles(prog_text, {
		'width': '100%'
	,	'height': '16px'
	,	'background-color': 'transparent'
	,	'position': 'absolute'
	,	'top': '0px'
	,	'left': '0px'
	,	'text-align': 'center'
	,	'z-index': '2'
	});
	JCalPro.setStyles(errors, {'color': 'red'});
	// construct the progress bar
	JCalPro.inject(progress, bar);
	JCalPro.inject(prog_text, bar);
	// construct the migration div
	JCalPro.inject(errors, container);
	JCalPro.inject(status, container);
	JCalPro.inject(bar, container);
	// inject container into page
	JCalPro.inject(container, buttons, 'before');
	// remove the buttons
	JCalPro.destroy(buttons);
	// set initial status text
	JCalPro.setText(status, window.jcl_migration_text.contacting_host);
	// this request could fail - we need to be prepared for this
	try {
		// start the migration process
		var req = JCalPro.request({
			url: 'index.php?option=com_jcalpro&task=install.migratecollect&format=json'
		,	requestType: 'json'
		,	onSuccess: function(responseJSON, responseText) {
				// bad request?
				if ('object' != typeof responseJSON) {
					jcl_migrate_set_error(window.jcl_migration_text.bad_request);
					return;
				}
				// handle errors
				if (responseJSON.errorText) {
					jcl_migrate_set_error(responseJSON.errorText);
					jcl_migrate_set_progress(1, 1);
					return;
				}
				JCalPro.each(['calendars', 'categories', 'events'], function(type, idx) {
					JCalPro.each(responseJSON.pks[type], function(pk, i) {
						pending.push({type: type, pk: pk});
					});
				});
				jcl_migrate_batch(0);
			}
		});
	}
	catch (err) {
		jcl_migrate_set_error(err);
		jcl_migrate_set_progress(1, 1);
	}
})();
