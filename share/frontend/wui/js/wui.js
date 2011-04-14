/*****************************************************************************
 *
 * wui.js - Functions which are used by the WUI
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
 
var cpt_clicks = 0;
var coords = '';
var objtype = '';
var follow_mouse = false;
var action_click = "";
var myshape = null;
var myshape_background = null;
var myshapex = 0;
var myshapey = 0;
var objid = -1;
var viewType = '';

/************************************************
 * Register events
 *************************************************/

// First firefox and the IE
if (window.addEventListener) {
  window.addEventListener("mousemove", function(e) {
    track_mouse(e);
    dragObject(e);
    return false;
  }, false);
} else {
  document.documentElement.onmousemove  = function(e) {
    track_mouse(e);
    dragObject(e);
    return false;
  };
}

// functions used to track the mouse movements, when the user is adding an object. Draw a line a rectangle following the mouse
// when the user has defined enough points we open the "add object" window
function get_click(newtype,nbclicks,action) {
	coords = '';
	action_click = action;
	objtype = newtype;
	
	// we init the number of points coordinates we're going to wait for before we display the add object window
	cpt_clicks=nbclicks;
	
	if(document.images['background']) {
		document.images['background'].style.cursor = 'crosshair';
	}
	
	document.onclick = get_click_pos;
}

function track_mouse(e) {
	if(follow_mouse) {
		var event;
		if(!e) {
			event = window.event;
		} else {
			event = e;
		}
		
		if (event.pageX || event.pageY) {
			posx = event.pageX;
			posy = event.pageY;
		} else if (event.clientX || event.clientY) {
			posx = event.clientX;
			posy = event.clientY;
		}
		
		// Substract height of header menu here
		posy -= getHeaderHeight();
		
		myshape.clear();
		
		if(objtype != 'textbox') {
			myshape.drawLine(myshapex, myshapey, posx, posy);
		} else {
			myshape.drawRect(myshapex, myshapey, (posx - myshapex), (posy - myshapey));
		}
		
		myshape.paint();
	}
	
	return true;
}

function get_click_pos(e) {
	if(cpt_clicks > 0) {
		var posx = 0;
		var posy = 0;
		
		var event;
		if(!e) {
			event = window.event;
		} else {
			event = e;
		}
	
		if (event.pageX || event.pageY) {
			posx = event.pageX;
			posy = event.pageY;
		}
		else if (event.clientX || event.clientY) {
			posx = event.clientX;
			posy = event.clientY;
		}

		// FIXME: Check the clicked area. Only handle clicks on the map!
		
		// Substract height of header menu here
		posy -= getHeaderHeight();
		
		// Start drawing a line
		if(cpt_clicks == 2) {		
			myshape = new jsGraphics("mymap");
			myshapex = posx;
			// Substract height of header menu here
			myshapey = posy;
			
			myshape.setColor('#06B606');
			myshape.setStroke(1);
			
			follow_mouse = true;
			
			// Save view_type for default selection in addmodify dialog
			viewType = 'line';
		}
		
		if(viewType == '') {
			viewType = 'icon';
		}
		
		// When a grid is enabled align the dragged object in the nearest grid
		if(oViewProperties.grid_show === 1) {
			var aCoords = coordsToGrid(posx, posy);
			posx = aCoords[0];
			posy = aCoords[1];
		}
		
		// Save current click position
		coords = coords + posx + ',' + posy + ',';
		
		// Reduce number of clicks left
		cpt_clicks = cpt_clicks - 1;
	}
	
	if(cpt_clicks == 0) {
		if(follow_mouse) {
			myshape.clear();
		}
		
		coords = coords.substr(0, coords.length-1);
		
		if(document.images['background']) {
			document.images['background'].style.cursor = 'default';
		}
		
		follow_mouse = false;
		var sUrl = '';
		if(action_click == 'add' || action_click == 'clone') {
			sUrl = oGeneralProperties.path_server+'?mod=Map&act=addModify&do=add&show='+mapname+'&type='+objtype+'&coords='+coords+'&viewType='+viewType;
			
			if(action_click == 'clone' && objid !== -1) {
				sUrl += '&clone='+objid;
			}
		} else if(action_click == 'modify' && objid !== -1) {
			sUrl = oGeneralProperties.path_server+'?mod=Map&act=addModify&do=modify&show='+mapname+'&type='+objtype+'&id='+objid+'&coords='+coords;
		}

		if(sUrl === '')
			return false;
		
		showFrontendDialog(sUrl, printLang(lang['properties'], ''));
		
		objid = -1;
		cpt_clicks = -1;
	}	
}

function saveObjectAfterResize(oObj) {
	// Split id to get object information
	var arr = oObj.id.split('_');
	
	var type = arr[1];
	var id = arr[2];
	
	var objX = pxToInt(oObj.style.left);
	var objY = pxToInt(oObj.style.top);
	var objW = parseInt(oObj.style.width);
	var objH = parseInt(oObj.style.height);

	if(!isInt(objX) || !isInt(objY) || !isInt(objW) || !isInt(objH)) {
		alert('ERROR: Invalid coords ('+objX+'/'+objY+'/'+objW+'/'+objH+'). Terminating.');
		return false;
	}
	
	// Don't forget to substract height of header menu
	var url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type='+type+'&id='+id+'&x='+objX+'&y='+objY+'&w='+objW+'&h='+objH;
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	
	oResult = null;
}

// Moved to frontend
function coordsToGrid(x, y) {
	var gridMoveX = x - (x % oViewProperties.grid_steps);
	var gridMoveY = y - (y % oViewProperties.grid_steps);
	return [ gridMoveX, gridMoveY ];
}

function saveObjectAfterMoveAndDrop(oObj) {
	// Split id to get object information
	var arr = oObj.id.split('_');

	var borderWidth = 3;
	if(arr[1] == 'label' || arr[1] == 'textbox')
			borderWidth = 0;
	
	// When a grid is enabled align the dragged object in the nearest grid
	if(oViewProperties.grid_show === 1) {
		var coords = coordsToGrid(oObj.x + borderWidth, oObj.y + borderWidth);
		oObj.x = (coords[0] - borderWidth);
		oObj.y = (coords[1] - borderWidth);
		oObj.style.top  = oObj.y + 'px';
		oObj.style.left = oObj.x + 'px';
		moveRelativeObject(oObj.id, oObj.y, oObj.x);
	}
	
	// Handle different ojects (Normal icons and labels)
	var type, id, url = '';
	if(arr[1] === 'label') {
		var align = arr[0];
		type = arr[2];
		id = arr[3];
		var x, y;
		
		// Handle relative and absolute aligned labels
		if(align === 'rel') {
			var objX, objY;
			var oLine = document.getElementById('line_'+type+'_'+id);
			if(oLine) {
				objX = document.getElementById('line_'+type+'_'+id).startX;
				objY = document.getElementById('line_'+type+'_'+id).startY;
				oLine = null;
			} else {	
				// Calculate relative coordinates
				objX = pxToInt(document.getElementById('box_'+type+'_'+id).style.left);
				objY = pxToInt(document.getElementById('box_'+type+'_'+id).style.top);
			}

			// +3: Is the borderWidth of the object highlighting.
			// The header menu height is not needed when calculating relative coords
			x = oObj.x - objX + borderWidth;
			y = oObj.y - objY + borderWidth;
			
			// Add + sign to mark relative positive coords (On negative relative coord
			// the - sign is added automaticaly
			// %2B is escaped +
			if(x >= 0)
				x = '%2B'+x;
			if(y >= 0)
				y = '%2B'+y;
		} else {
			x = oObj.x;
			y = oObj.y;
		}
		
		url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type='+type+'&id='+id+'&label_x='+x+'&label_y='+y;
	} else {
		type = arr[1];
		id = arr[2];

		// +3: Is the borderWidth of the object highlighting
		x = oObj.x + borderWidth;
		y = oObj.y + borderWidth;

		// Don't forget to substract height of header menu
		if(isInt(x) && isInt(y)) {
			url = oGeneralProperties.path_server+'?mod=Map&act=modifyObject&map='+mapname+'&type='+type+'&id='+id+'&x='+x+'&y='+y;
		} else {
			alert('ERROR: Invalid coords ('+x+'/'+y+'). Terminating.');
			return false;
		}
	}
	
	// Sync ajax request
	var oResult = getSyncRequest(url);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
	}
	oResult = null;
}

function getObjectIdOfLink(obj) {
	var par = obj.parentNode;
	while(par && par.tagName != 'DIV')
		par = par.parentNode;
	if(par && par.tagName == 'DIV' && typeof par.id !== 'undefined')
		return par.id.split('_')[2].split('-')[0];
	return -1;
}

function getDomObjectIds(objId) {
    return [ 'box_'+objId, 'icon_'+objId, 'icon_'+objId+'-context', 'rel_label_'+objId, 'abs_label_'+objId ];
}

// This function handles object deletions on maps
function deleteMapObject(objId) {
	if(confirm(printLang(lang['confirmDelete'],''))) {
		var arr = objId.split('_');
		var map = mapname;
		var type = arr[1];
		var id = arr[2];
		
		// Sync ajax request
		var oResult = getSyncRequest(oGeneralProperties.path_server+'?mod=Map&act=deleteObject&map='+map+'&type='+type+'&id='+id);
		if(oResult && oResult.status != 'OK') {
			alert(oResult.message);
			return false;
		}
		oResult = null;

		// Remove the object with all childs and other containers from the map
		var oMap = document.getElementById('mymap');
		var ids = getDomObjectIds(type+'_'+id)
		for(var i in ids) {
			var o = document.getElementById(ids[i])
			if(o) {
				oMap.removeChild(o);
				o = null;
			}
		}
		oMap = null;

		// Now change all objects of the same type which have a higher object id.
		// The object id of these objects needs to be reduced by one.
		var oObj, domIds;
		var newId = parseInt(id);
		var nextId = newId+1;
		while(document.getElementById('box_'+type+'_'+nextId)) {
			domIds = getDomObjectIds(type+'_'+nextId);
			for(var i in domIds) {
				var oObj = document.getElementById(domIds[i]);
				if(oObj)
					oObj.setAttribute('id', domIds[i].replace(nextId, newId));
			}
			nextId++;
			newId++;
		}
    nextId = null;
    newId = null;
		oObj = null;
		
		return true;
	} else {
		return false;
	}
}

/**
 * formSubmit()
 *
 * Submits the form contents to the ajax handler by a synchronous HTTP-GET
 *
 * @param   String   ID of the form
 * @param   String   Action to send to the ajax handler
 * @return  Boolean  
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function formSubmit(formId, target, bReloadPage) {
	if(typeof bReloadPage === 'undefined') {
		bReloadPage = true;
	}
	
	// Read form contents
	var getstr = getFormParams(formId);
	
	// Submit data
	var oResult = getSyncRequest(target+'&'+getstr);
	if(oResult && oResult.status != 'OK') {
		alert(oResult.message);
		return false;
	}
	oResult = null;
	
	// Close form
	popupWindowClose();
	
	// FIXME: Reloading the map (Change to reload object)
	if(bReloadPage === true) {
		document.location.href='./index.php?mod=Map&act=edit&show='+mapname;
	}
}

/**
 * toggleBorder()
 *
 * Highlights an object by show/hide a border around the icon
 *
 * @param   Object   Object to draw the border arround
 * @param   Integer  Enable/Disabled border
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
function toggleBorder(oObj, state){
	var sColor = '#dddddd';
	var iWidth = 3;
	
	var oContainer = oObj.parentNode;

	var top = pxToInt(oContainer.style.top);
	var left = pxToInt(oContainer.style.left);

	var parts = oObj.id.split('_');
	var type  = parts[1];
  var id    = parts[2];
  var oLine = document.getElementById('line_'+type+'_'+id);

	if(state === 1) {
		oObj.style.border = iWidth + "px solid " + sColor;
		oContainer.style.top  = (top - iWidth) + 'px';
		oContainer.style.left = (left - iWidth) + 'px';

		if(oLine) {
			oLine.style.top  = iWidth + 'px';
			oLine.style.left = iWidth + 'px';
		}
	} else {
		oObj.style.border = "none";
		oContainer.style.top = (top + iWidth) + 'px';
		oContainer.style.left = (left + iWidth) + 'px';

		if(oLine) {
			oLine.style.top  = '0px';
			oLine.style.left = '0px';
		}
	}
	
	oLine = null;
	oObj = null;
	oContainer = null;
}
