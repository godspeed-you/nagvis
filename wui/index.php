<?php
##########################################################################
##      NagVis WUI - Addon to edit the configuration in the browser     ##
##########################################################################
## index.php - Main file to get called by the user			            ##
##########################################################################
## Licenced under the terms and conditions of the GPL Licence,         	##
## please see attached "LICENCE" file	                                ##
##########################################################################

##########################################################################
## For developer guidlines have a look at http://www.nagvis.org			##
##########################################################################

require("../nagvis/includes/defines/global.php");
require("../nagvis/includes/defines/matches.php");

require("../nagvis/includes/functions/debug.php");

require("../nagvis/includes/classes/GlobalMainCfg.php");
require("../nagvis/includes/classes/GlobalMapCfg.php");
require("../nagvis/includes/classes/GlobalLanguage.php");
require("../nagvis/includes/classes/GlobalPage.php");
require("../nagvis/includes/classes/GlobalMap.php");
require("../nagvis/includes/classes/GlobalGraphic.php");

require("./includes/classes/WuiMainCfg.php");
require("./includes/classes/WuiMapCfg.php");

$MAINCFG = new WuiMainCfg(CONST_MAINCFG);

// If not set, initialize $_GET['page']
if(!isset($_GET['page'])) {
	$_GET['page'] = '';	
}

// Display the wanted page, if nothing is set, display the map
switch($_GET['page']) {
	case 'edit_config':
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiEditMainCfg.php");
		
		$FRONTEND = new WuiEditMainCfg($MAINCFG);
		$FRONTEND->getForm();
		$FRONTEND->printPage();
	break;
	case 'shape_management':
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiShapeManagement.php");
		$FRONTEND = new WuiShapeManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'background_management':
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiBackgroundManagement.php");
		$FRONTEND = new WuiBackgroundManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'map_management':
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiMapManagement.php");
		$FRONTEND = new WuiMapManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'backend_management':
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiBackendManagement.php");
		$FRONTEND = new WuiBackendManagement($MAINCFG);
		$FRONTEND->getForm();
	break;
	case 'addmodify':
		require("../nagvis/includes/classes/GlobalForm.php");
		require("./includes/classes/WuiAddModify.php");
		
		$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
		$MAPCFG->readMapConfig();
		
		if(!isset($_GET['coords'])) {
			$_GET['coords'] = '';
		}
		if(!isset($_GET['id'])) {
			$_GET['id'] = '';
		}
		
		$FRONTEND = new WuiAddModify($MAINCFG,$MAPCFG,Array('action' => $_GET['action'],
															'type' => $_GET['type'],
															'id' => $_GET['id'],
															'coords' => $_GET['coords']));
		$FRONTEND->getForm();
	break;
	default:
		require("./includes/classes/WuiFrontend.php");
		require("./includes/classes/WuiMap.php");
		
		if(!isset($_GET['map'])) {
			$_GET['map'] = '';	
		}
		
		$MAPCFG = new WuiMapCfg($MAINCFG,$_GET['map']);
		$MAPCFG->readMapConfig();
		
		$FRONTEND = new WuiFrontend($MAINCFG,$MAPCFG);
		$FRONTEND->getMap();
		$FRONTEND->getMessages();
		
		if($_GET['map'] != '') {
			if(!$MAPCFG->checkMapConfigWriteable(1)) {
				exit;
			}
			if(!$MAPCFG->checkMapImageExists(1)) {
				exit;
			}
			if(!$MAPCFG->checkMapImageReadable(1)) {
				exit;
			}
    		if(!$MAPCFG->checkMapLocked(1)) {
    		    $MAPCFG->writeMapLock();
    		}
		}
	break;
}
		
// print the HTML page
$FRONTEND->printPage();
?>
