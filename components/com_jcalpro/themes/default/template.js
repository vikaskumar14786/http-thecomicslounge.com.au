/*
 **********************************************
 Copyright (c) 2006-2011 Anything-Digital.com
 **********************************************
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 This header must not be removed. Additional contributions/changes
 may be added to this header as long as no information is deleted.
 **********************************************
 $Id: template.js 726 2011-08-12 19:02:19Z jeffchannell $
 */

function preloadImage() {
  var args = preloadImage.arguments;
  document.imageArray[args[0]] = new Array(args.length - 1);

  for ( var i = 1; i < args.length; i++) {
    document.imageArray[args[0]][i - 1] = new Image;
    document.imageArray[args[0]][i - 1].src = args[i];
  }
}

function showOnBar(Str) {
  window.status = Str;
  return true;
}

function cOn(myObject, color) {
  if (document.getElementById || (document.all && !(document.getElementById))) {
    if (!color)
      color = "#6187E5"

    myObject.style.backgroundColor = color;
  }
}

function cOut(myObject, color) {
  if (document.getElementById || (document.all && !(document.getElementById))) {
    if (!color)
      color = "#5177C5"

    myObject.style.backgroundColor = color;
  }
}

function cal_switchImage(imgName, imgSrc) {
  if (document.images) {
    if (imgSrc != "none") {
      document.images[imgName].src = imgSrc;
    }
  }
}

function verify(msg) {
  if (!msg)
    msg = "Are you absolutely sure that you want to delete this item?";

  // all we have to do is return the return value of the confirm() method
  return confirm(msg);
}

function jclGetElement(psID) {
  if (document.all) {
    return document.all[psID];
  }

  else if (document.getElementById) {
    return document.getElementById(psID);
  }

  else {
    for (iLayer = 1; iLayer < document.layers.length; iLayer++) {
      if (document.layers[iLayer].id == psID)
        return document.layers[iLayer];
    }
  }

  return Null;
}

/*
 * returns a cookie variable with the given name.
 */
function jclGetCookie(name) {
  var dc = document.cookie;
  var prefix = extcal_cookie_id + '_' + name + "=";
  var begin = dc.indexOf("; " + prefix);

  if (begin == -1) {
    begin = dc.indexOf(prefix);

    if (begin != 0)
      return null;
  }

  else {
    begin += 2;
  }

  var end = document.cookie.indexOf(";", begin);

  if (end == -1) {
    end = dc.length;
  }

  return unescape(dc.substring(begin + prefix.length, end));
}

/*
 * Sets a Cookie with the given name and value.
 */
function jclSetCookie(name, value, persistent) {
  var today = new Date();
  var expiry = new Date(today.getTime() + 364 * 24 * 60 * 60 * 1000); // 364
  // days
  var expires = "";
  var domain = extcal_cookie_domain;
  var path = extcal_cookie_path;
  var secure = false;
  var prefix = extcal_cookie_id + '_' + name + "=";

  if (persistent) {
    expires = "; expires = " + expiry.toGMTString();
  }

  document.cookie = prefix + escape(value) + ((expires) ? expires : "")
  + ((path) ? "; path=" + path : "")
  + ((domain) ? "; domain=" + domain : "")
  + ((secure) ? "; secure" : "") + ';';
}

// ==========================================
// Set DIV ID to hide
// ==========================================

function jcl_hide_div(itm) {
  if (!itm)
    return;

  itm.style.display = "none";
}

// ==========================================
// Set DIV ID to show
// ==========================================

function jcl_show_div(itm) {
  if (!itm)
    return;

  itm.style.display = "";
}

// ==========================================
// Toggle category
// ==========================================

function togglecategory(fid, add) {
  saved = new Array();
  clean = new Array();

  // ==========================================
  // Get any saved info
  // ==========================================

  if (tmp = jclGetCookie('collapseprefs')) {
    saved = tmp.split(",");
  }

  // ==========================================
  // Remove bit if exists
  // ==========================================

  for (i = 0; i < saved.length; i++) {
    if (saved[i] != fid && saved[i] != "") {
      clean[clean.length] = saved[i];
    }
  }

  // ==========================================
  // Add?
  // ==========================================

  if (add) {
    clean[clean.length] = fid;
    jcl_show_div(jclGetElement(fid + '_close'));
    jcl_hide_div(jclGetElement(fid + '_open'));
  }

  else {
    jcl_show_div(jclGetElement(fid + '_open'));
    jcl_hide_div(jclGetElement(fid + '_close'));
  }

  jclSetCookie('hidden_display', clean.join(','), 1);
}

// sets dynamically the content of a given html tag id
function jclSetText(id, value) {
  var label = jclGetElement(id);
  label.firstChild.nodeValue = value;
}

//sets dynamically the content of an element
function jclSetChecked(id, value) {
  var element = jclGetElement(id);
  element.checked = value;
}

// shows recurrence options div, hiding all others
function jclShowRecOptions(id) {
  var divs = new Array('jcl_rec_none_options', 'jcl_rec_daily_options',
    'jcl_rec_weekly_options', 'jcl_rec_monthly_options',
    'jcl_rec_yearly_options');
  var target = '';
  if (id) {
    target = 'jcl_rec_' + id + '_options';
  }
  for (i = 0; i < divs.length; i++) {
    if (divs[i] == target) {
      jcl_show_div(jclGetElement(divs[i]));
    } else {
      jcl_hide_div(jclGetElement(divs[i]));
    }
  }
}

function printDocument() {
  self.focus();
  self.print();
}
