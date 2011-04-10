<?php
/*****************************************************************************
 *
 * NagVisHeaderMenu.php - Class for handling the header menu
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
class NagVisHeaderMenu {
	private $CORE;
	private $AUTHORISATION;
	private $UHANDLER;
	private $OBJ;
	private $TMPL;
	private $TMPLSYS;
	
	private $templateName;
	private $pathHtmlBase;
	private $pathTemplateFile;
	
	private $aMacros = Array();
	private $bRotation = false;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __construct($CORE, CoreAuthorisationHandler $AUTHORISATION, CoreUriHandler $UHANDLER, $templateName, $OBJ = null) {
		$this->CORE = $CORE;
		$this->AUTHORISATION = $AUTHORISATION;
		$this->UHANDLER = $UHANDLER;
		$this->OBJ = $OBJ;
		$this->templateName = $templateName;
		
		$this->pathHtmlBase = $this->CORE->getMainCfg()->getValue('paths','htmlbase');
		$this->pathTemplateFile = $this->CORE->getMainCfg()->getPath('sys', '', 'templates', $this->templateName.'.header.html');
		
		// Initialize template system
		$this->TMPL = New FrontendTemplateSystem($this->CORE);
		$this->TMPLSYS = $this->TMPL->getTmplSys();
		
		// Read the contents of the template file
		$this->checkTemplateReadable(1);
	}
	
	/**
	 * PUBLIC setRotationEnabled()
	 *
	 * Tells the header menu that the current view is rotating
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function setRotationEnabled() {
		$this->bRotation = true;
	}
	
	/**
	 * Print the HTML code
	 *
	 * return   String  HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	public function __toString() {
		// Get all macros
		$this->getMacros();
		
		// Build page based on the template file and the data array
		return $this->TMPLSYS->get($this->TMPL->getTmplFile($this->templateName, 'header'), $this->aMacros);
	}

	/**
	 * Returns a list of available languages for the header menus macro list
	 *
	 * return   Array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getLangList() {
		// Build language list
		$aLang = $this->CORE->getAvailableAndEnabledLanguages();
		$numLang = count($aLang);
		foreach($aLang AS $lang) {
			$aLangs[$lang] = Array();
			$aLangs[$lang]['language'] = $lang;
			
			// Get translated language name
			switch($lang) {
				case 'en_US':
					$languageLocated = $this->CORE->getLang()->getText('en_US');
				break;
				case 'de_DE':
					$languageLocated = $this->CORE->getLang()->getText('de_DE');
				break;
				case 'es_ES':
					$languageLocated = $this->CORE->getLang()->getText('es_ES');
				break;
				case 'fr_FR':
					$languageLocated = $this->CORE->getLang()->getText('fr_FR');
				break;
				case 'pt_BR':
					$languageLocated = $this->CORE->getLang()->getText('pt_BR');
				break;
				case 'ru_RU':
					$languageLocated = $this->CORE->getLang()->getText('ru_RU');
				break;
				default:
					$languageLocated = $this->CORE->getLang()->getText($lang);
				break;
			}
			
			$aLangs[$lang]['langLanguageLocated'] = $languageLocated;
		}
		return $aLangs;
	}

	/**
	 * Returns a list of maps/automaps for the header menus macro list
	 *
	 * return   Array
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getMapList($type, $maps) {
		$permEditAnyMap = false;
		$aMaps = Array();
		$childMaps = Array();
		foreach($maps AS $mapName) {
			$map = Array();
			
			if($type == 'maps')
				$MAPCFG1 = new NagVisMapCfg($this->CORE, $mapName);
			else
				$MAPCFG1 = new NagVisAutomapCfg($this->CORE, $mapName);
			try {
				$MAPCFG1->readMapConfig(ONLY_GLOBAL);
			} catch(MapCfgInvalid $e) {
				$map['configError'] = true;
			}
			
			// Only show maps which should be shown
			if($MAPCFG1->getValue(0, 'show_in_lists') != 1)
				continue;

			// Only proceed permited objects
			if($this->CORE->getAuthorization() === null
			   || (($type == 'maps' && !$this->CORE->getAuthorization()->isPermitted('Map', 'view', $mapName))
				     || ($type == 'automaps' && !$this->CORE->getAuthorization()->isPermitted('AutoMap', 'view', $mapName))))
				continue;
			
			$map['mapName'] = $MAPCFG1->getName();
			$map['mapAlias'] = $MAPCFG1->getValue(0, 'alias');
			$map['childs'] = Array();
			if($type == 'maps') {
				$map['urlParams'] = '';
				$map['permittedEdit'] = $this->CORE->getAuthorization()->isPermitted('Map', 'edit', $mapName);

				$permEditAnyMap |= $map['permittedEdit'];
			} else
				$map['urlParams'] = str_replace('&', '&amp;', $MAPCFG1->getValue(0, 'default_params'));
			
			// auto select current map and apply map specific optins to the header menu
			if($this->OBJ !== null && ($this->aMacros['mod'] == 'Map' || $this->aMacros['mod'] == 'AutoMap') && $mapName == $this->OBJ->getName()) {
				$map['selected'] = True;
				
				// Override header fade option with map config
				$this->aMacros['bEnableFade'] = $MAPCFG1->getValue(0, 'header_fade');
			}
			
			$map['parent'] = $MAPCFG1->getValue(0, 'parent_map');

			if($map['parent'] === '')
				$aMaps[$map['mapName']] = $map;
			else {
				if(!isset($childMaps[$map['parent']]))
					$childMaps[$map['parent']] = Array();
				$childMaps[$map['parent']][$map['mapName']] = $map;
			}
		}

		return Array($this->mapListToTree($aMaps, $childMaps), $permEditAnyMap);
	}

	private function mapListToTree($maps, $childMaps) {
		foreach(array_keys($maps) AS $freeParent)
			if(isset($childMaps[$freeParent]))
				$maps[$freeParent]['childs'] = $this->mapListToTree($childMaps[$freeParent], $childMaps);
		return $maps;
	}
	
	/**
	 * PRIVATE getMacros()
	 *
	 * Returns all macros for the header template
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getMacros() {
		// First get all static macros
		$this->aMacros = $this->getStaticMacros();
		
		// Save the page
		$this->aMacros['mod'] = $this->UHANDLER->get('mod');
		$this->aMacros['act'] = $this->UHANDLER->get('act');
		
		// In rotation?
		$this->aMacros['bRotation'] = $this->bRotation;
		
		$this->aMacros['permittedOverview'] = $this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted('Overview', 'view', '*');
		
		// Check if the user is permitted to edit the current map/automap
		$this->aMacros['permittedView'] = $this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted($this->aMacros['mod'], 'view', $this->UHANDLER->get('show'));
		$this->aMacros['permittedEdit'] = $this->CORE->getAuthorization() !== null && $this->CORE->getAuthorization()->isPermitted($this->aMacros['mod'], 'edit', $this->UHANDLER->get('show'));

		// Permissions for the option menu
		$this->aMacros['permittedSearch']            = $this->AUTHORISATION->isPermitted('Search', 'view', '*');
		$this->aMacros['permittedEditMainCfg']       = $this->AUTHORISATION->isPermitted('MainCfg', 'edit', '*');
		$this->aMacros['permittedManageShapes']      = $this->AUTHORISATION->isPermitted('ManageShapes', 'manage', '*');
		$this->aMacros['permittedManageBackgrounds'] = $this->AUTHORISATION->isPermitted('ManageBackgrounds', 'manage', '*');
		$this->aMacros['permittedManageBackgrounds'] = $this->AUTHORISATION->isPermitted('ManageBackgrounds', 'manage', '*');
		$this->aMacros['permittedManageMaps']        = $this->AUTHORISATION->isPermitted('Map', 'add', '*') && $this->AUTHORISATION->isPermitted('Map', 'edit', '*');
		
		$this->aMacros['currentUser'] = $this->CORE->getAuthentication()->getUser();
		
		$this->aMacros['permittedChangePassword'] = $this->AUTHORISATION->isPermitted('ChangePassword', 'change', '*');
		
		$this->aMacros['permittedLogout'] = $this->CORE->getAuthentication()->logoutSupported()
                                        & $this->AUTHORISATION->isPermitted('Auth', 'logout', '*');
		
		// Replace some special macros
		if($this->OBJ !== null && ($this->aMacros['mod'] == 'Map' || $this->aMacros['mod'] == 'AutoMap')) {
			$this->aMacros['currentMap'] = $this->OBJ->getName();
			$this->aMacros['currentMapAlias'] = $this->OBJ->getValue(0, 'alias');
		} else {
			$this->aMacros['currentMap'] = '';
			$this->aMacros['currentMapAlias'] = '';
		}
		
		// Initialize the enable fade option. Is overridden by the current map or left as is
		$this->aMacros['bEnableFade'] = $this->CORE->getMainCfg()->getValue('defaults', 'headerfade');
		
		list($this->aMacros['maps'], $this->aMacros['permittedEditAnyMap']) = $this->getMapList('maps', $this->CORE->getAvailableMaps());
		list($this->aMacros['automaps'], $_not_used) = $this->getMapList('automaps', $this->CORE->getAvailableAutomaps());
		$this->aMacros['langs'] = $this->getLangList();
	}
	
	/**
	 * PRIVATE getStaticMacros()
	 *
	 * Get all static macros for the template code
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function getStaticMacros() {
		$SHANDLER = new CoreSessionHandler();
		
		// Replace paths and language macros
		$aReturn = Array('pathBase' => $this->pathHtmlBase,
			'currentUri'         => preg_replace('/[&?]lang=[a-z]{2}_[A-Z]{2}/', '', $this->UHANDLER->getRequestUri()),
			'pathImages'         => $this->CORE->getMainCfg()->getValue('paths','htmlimages'), 
			'pathHeaderJs'       => $this->CORE->getMainCfg()->getPath('html', 'global', 'templates', $this->templateName.'.header.js'), 
			'pathTemplates'      => $this->CORE->getMainCfg()->getPath('html', 'global', 'templates'), 
			'pathTemplateImages' => $this->CORE->getMainCfg()->getPath('html', 'global', 'templateimages'),
			'langSearch' => $this->CORE->getLang()->getText('Search'),
			'langUserMgmt' => $this->CORE->getLang()->getText('Manage Users'),
			'langManageRoles' => $this->CORE->getLang()->getText('Manage Roles'),
			'langWui' => $this->CORE->getLang()->getText('WUI'),
			'currentLanguage' => $this->CORE->getLang()->getCurrentLanguage(),
			'langChooseLanguage' => $this->CORE->getLang()->getText('Choose Language'),
			'langUser' => $this->CORE->getLang()->getText('User menu'),
			'langActions' => $this->CORE->getLang()->getText('Actions'),
			'langLoggedIn' => $this->CORE->getLang()->getText('Logged in'),
			'langChangePassword' => $this->CORE->getLang()->getText('Change password'),
			'langOpen' => $this->CORE->getLang()->getText('Open'),
			'langMap' => $this->CORE->getLang()->getText('Map'),
			'langMapOptions' => $this->CORE->getLang()->getText('Map Options'),
			'langMapManageTmpl' => $this->CORE->getLang()->getText('Manage Templates'),
			'langMapAddIcon' => $this->CORE->getLang()->getText('Add Icon'),
			'langMapAddLine' => $this->CORE->getLang()->getText('Add Line'),
			'langLine' => $this->CORE->getLang()->getText('Line'),
			'langMapAddSpecial' => $this->CORE->getLang()->getText('Add Special'),
			'langHost' => $this->CORE->getLang()->getText('host'),
			'langService' => $this->CORE->getLang()->getText('service'),
			'langHostgroup' => $this->CORE->getLang()->getText('hostgroup'),
			'langServicegroup' => $this->CORE->getLang()->getText('servicegroup'),
			'langMapEdit' => $this->CORE->getLang()->getText('Edit Map'),
			'langMaps' => $this->CORE->getLang()->getText('Maps'),
			'langAutomaps' => $this->CORE->getLang()->getText('Automaps'),
			'langTextbox' => $this->CORE->getLang()->getText('textbox'),
			'langShape' => $this->CORE->getLang()->getText('shape'),
			'langStateless' => $this->CORE->getLang()->getText('Stateless'),
			'langSpecial' => $this->CORE->getLang()->getText('special'),
			'langEditMap' => $this->CORE->getLang()->getText('editMap'),
			'langLockUnlockAll' => $this->CORE->getLang()->getText('Lock/Unlock all'),
			'langViewMap' => $this->CORE->getLang()->getText('View current map'),
			'langOptions' => $this->CORE->getLang()->getText('Options'),
			'langWuiConfiguration' => $this->CORE->getLang()->getText('General Configuration'),
			'langMgmtBackends' => $this->CORE->getLang()->getText('Manage Backends'),
			'langMgmtBackgrounds' => $this->CORE->getLang()->getText('Manage Backgrounds'),
			'langMgmtMaps' => $this->CORE->getLang()->getText('Manage Maps'),
			'langMgmtShapes' => $this->CORE->getLang()->getText('Manage Shapes'),
			'langNeedHelp' => $this->CORE->getLang()->getText('needHelp'),
			'langOnlineDoc' => $this->CORE->getLang()->getText('onlineDoc'),
			'langForum' => $this->CORE->getLang()->getText('forum'),
			'langSupportInfo' => $this->CORE->getLang()->getText('supportInfo'),
			'langOverview' => $this->CORE->getLang()->getText('overview'),
			'langInstance' => $this->CORE->getLang()->getText('instance'),
			'langLogout' => $this->CORE->getLang()->getText('Logout'),
			'langRotationStart' => $this->CORE->getLang()->getText('rotationStart'),
			'langRotationStop' => $this->CORE->getLang()->getText('rotationStop'),
			'langToggleGrid' => $this->CORE->getLang()->getText('Show/Hide Grid'),
			'langAutomapToMap' => $this->CORE->getLang()->getText('Export to Map'),
			'langModifyAutomapParams' => $this->CORE->getLang()->getText('Modify Automap view'),
			// Supported by backend and not using trusted auth
			'supportedChangePassword' => $this->CORE->getAuthentication()->checkFeature('changePassword') && !$this->CORE->getAuthentication()->authedTrusted(),
			'permittedUserMgmt' => $this->AUTHORISATION->isPermitted('UserMgmt', 'manage'),
			'permittedRoleMgmt' => $this->AUTHORISATION->isPermitted('RoleMgmt', 'manage'));
		
		return $aReturn;
	}
	
	/**
	 * Checks for readable header template
	 *
	 * @param 	Boolean	$printErr
	 * @return	Boolean	Is Check Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	private function checkTemplateReadable($printErr) {
		return GlobalCore::getInstance()->checkReadable($this->pathTemplateFile, $printErr);
	}
}
?>
