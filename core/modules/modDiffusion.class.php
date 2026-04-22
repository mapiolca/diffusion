<?php
/* Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019	Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2025-2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   diffusion     Module Diffusion
 *  \brief      Diffusion module descriptor.
 *
 *  \file       htdocs/diffusion/core/modules/modDiffusion.class.php
 *  \ingroup    diffusion
 *  \brief      Description and activation file for module Diffusion
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Diffusion
 */
class modDiffusion extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 450001; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'diffusion';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "Les Métiers du Bâtiment";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleDiffusionName' not found (Diffusion is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// DESCRIPTION_FLAG
		// Module description, used if translation string 'ModuleDiffusionDesc' not found (Diffusion is name of module).
		$this->description = 'ModuleDiffusionDesc';
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "DiffusionDescription";

		// Author
		$this->editor_name = 'Les Métiers du Bâtiment';
		$this->editor_url = 'lesmetiersdubatiment.fr';		// Must be an external online web site
		$this->editor_squarred_logo = '';					// Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@diffusion'

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.1';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where DIFFUSION is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

                if (!isset($conf->diffusion) || !is_object($conf->diffusion)) {
                        $conf->diffusion = new stdClass();
                }

                $entity = !empty($conf->entity) ? (int) $conf->entity : 1;
                $defaultDir = DOL_DATA_ROOT.($entity > 1 ? '/'.$entity : '').'/diffusion';

			if (empty($conf->diffusion->dir_temp)) {
				$conf->diffusion->dir_temp = $defaultDir.'/temp';
			}
               if (empty($conf->diffusion->multidir_output) || !is_array($conf->diffusion->multidir_output)) {
                       $conf->diffusion->multidir_output = array();
               }
               if (empty($conf->diffusion->multidir_output[$entity])) {
                       $conf->diffusion->multidir_output[$entity] = $defaultDir;
               }

			$diffusionDir = DOL_DATA_ROOT.($entity > 1 ? '/'.$entity : '').'/diffusion';
			if (!isset($conf->diffusion) || !is_object($conf->diffusion)) {
				$conf->diffusion = new stdClass();
			}
			if (empty($conf->diffusion->multidir_output) || !is_array($conf->diffusion->multidir_output)) {
				$conf->diffusion->multidir_output = array();
			}
			if (empty($conf->diffusion->multidir_output[$entity])) {
				$conf->diffusion->multidir_output[$entity] = $diffusionDir;
			}
			if (!isset($conf->diffusion->enabled)) {
				$conf->diffusion->enabled = (!empty($conf->diffusion->enabled) ? 1 : 0);
			}

			if (!isset($conf->diffusioncontact) || !is_object($conf->diffusioncontact)) {
				$conf->diffusioncontact = new stdClass();
			}

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-paper-plane';

		/// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/diffusion/css/diffusion.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				'/diffusion/js/diffusion.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			/* BEGIN MODULEBUILDER HOOKSCONTEXTS */
			'hooks' => array(
				'data' => array('projectoverview', 'projectcard', 'projectOverview', 'projectCard', 'projectoverviewprofit', 'projectOverviewProfit', 'globalcard', 'notification', 'emailtemplates', 'multicompanyexternalmodulesharing', 'multicompanyexternalmodules', 'multicompanysharingoptions'),
				'entity' => '0',
			),
			/* END MODULEBUILDER HOOKSCONTEXTS */
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
			// Set this to 1 if the module provides a website template into doctemplates/websites/website_template-mytemplate
			'websitetemplates' => 0,
			// Set this to 1 if the module provides a captcha driver
			'captcha' => 0
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/diffusion/temp","/diffusion/subdir");
                $this->dirs = array(
                        "/diffusion/temp",
                        "/diffusion/diffusion",
                );

		// Config pages. Put here list of php page, stored into diffusion/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@diffusion");

		// Dependencies
		// A condition to hide module
		$this->hidden = getDolGlobalInt('MODULE_DIFFUSION_DISABLED'); // A condition to disable module;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array('always'=>array('modProjet','modSociete'));
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("diffusion@diffusion");

		// Prerequisites
		$this->phpmin = array(7, 1); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(19, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'DiffusionWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('DIFFUSION_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('DIFFUSION_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$i = 0;
		$this->const = array(
			$i++ => ['DIFFUSION_DIFFUSION_ADDON', 'chaine', 'mod_diffusion_standard', '', 0, 'current'],
			$i++ => ['DIFFUSION_DIFFUSION_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT/diffusion/diffusion/', '', 0, 'current'],
			$i++ => ['DIFFUSION_DIFFUSION_DEFAULT_MODEL', 'chaine', 'standard_diffusion', '', 0, 'current'],
			$i++ => ['MAIN_AGENDA_ACTIONAUTO_DIFFUSION_VALIDATE', 'yesno', '1', '', 0, 'current'],
			$i++ => ['MAIN_AGENDA_ACTIONAUTO_DIFFUSION_BACKTODRAFT', 'yesno', '1', '', 0, 'current'],
			$i++ => ['MAIN_AGENDA_ACTIONAUTO_DIFFUSION_SENDMAIL', 'yesno', '1', '', 0, 'current'],
			$i++ => ['MAIN_AGENDA_ACTIONAUTO_DIFFUSION_SETDIFFUSED', 'yesno', '1', '', 0, 'current'],
			$i++ => ['MAIN_AGENDA_ACTIONAUTO_DIFFUSION_DELETE', 'yesno', '1', '', 0, 'current'],
		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isModEnabled("diffusion")) {
			$conf->diffusion = new stdClass();
			$conf->diffusion->enabled = 0;
		}

		// Array to add new pages in new tabs
		/* BEGIN MODULEBUILDER TABS */
		$this->tabs = array();
		$this->tabs[] = array(
		    'data'=>'project:+diffusion:DiffusionDocuments:diffusion@diffusion:$user->hasRight(\'diffusion\', \'read\'):/diffusion/tabs/diffusion.php?id=__ID__',
		    );
		/* END MODULEBUILDER TABS */
		// Example:
		// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data' => 'objecttype:+tabname1:Title1:mylangfile@diffusion:$user->hasRight(\'diffusion\', \'read\'):/diffusion/mynewtab1.php?id=__ID__');
		// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data' => 'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@diffusion:$user->hasRight(\'othermodule\', \'read\'):/diffusion/mynewtab2.php?id=__ID__',
		// To remove an existing tab identified by code tabname
		// $this->tabs[] = array('data' => 'objecttype:-tabname:NU:conditiontoremove');
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'delivery'         to add a tab in delivery view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sale order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		/* Example:
		 $this->dictionaries=array(
		 'langs' => 'diffusion@diffusion',
		 // List of tables we want to see into dictionary editor
		 'tabname' => array("table1", "table2", "table3"),
		 // Label of tables
		 'tablib' => array("Table1", "Table2", "Table3"),
		 // Request to select fields
		 'tabsql' => array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
		 // Sort order
		 'tabsqlsort' => array("label ASC", "label ASC", "label ASC"),
		 // List of fields (result of select to show dictionary)
		 'tabfield' => array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields to edit a record)
		 'tabfieldvalue' => array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields for insert)
		 'tabfieldinsert' => array("code,label", "code,label", "code,label"),
		 // Name of columns with primary key (try to always name it 'rowid')
		 'tabrowid' => array("rowid", "rowid", "rowid"),
		 // Condition to show each dictionary
		 'tabcond' => array(isModEnabled('diffusion'), isModEnabled('diffusion'), isModEnabled('diffusion')),
		 // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
		 'tabhelp' => array(array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		 );
		 */
		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array();
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in diffusion/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'diffusionwidget1.php@diffusion',
			//      'note' => 'Widget provided by Diffusion',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/diffusion/class/diffusion.class.php',
			//      'objectname' => 'Diffusion',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("diffusion")',
			//      'priority' => 50,
			//  ),
		);
		/* END MODULEBUILDER CRON */
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("diffusion")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("diffusion")', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 0 + 1);
		$this->rights[$r][1] = 'ReadDiffusion';
		$this->rights[$r][4] = 'diffusiondoc';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 1 + 1);
		$this->rights[$r][1] = 'CreateDiffusion';
		$this->rights[$r][4] = 'diffusiondoc';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 2 + 1);
		$this->rights[$r][1] = 'DeleteDiffusion';
		$this->rights[$r][4] = 'diffusiondoc';
		$this->rights[$r][5] = 'delete';
		$r++;
		/*
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 0 + 1);
		$this->rights[$r][1] = 'ReadDiffusionContact';
		$this->rights[$r][4] = 'diffusioncontact';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 1 + 1);
		$this->rights[$r][1] = 'CreateDiffusionContact';
		$this->rights[$r][4] = 'diffusioncontact';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (1 * 10) + 2 + 1);
		$this->rights[$r][1] = 'DeleteDiffusionContact';
		$this->rights[$r][4] = 'diffusioncontact';
		$this->rights[$r][5] = 'delete';
		$r++;
		*/
		/* END MODULEBUILDER PERMISSIONS */


		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		/* END MODULEBUILDER TOPMENU */



				/* BEGIN MODULEBUILDER LEFTMENU DIFFUSION */
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=tools',
			'type' => 'left',
			'titre' => 'Diffusions',
			'prefix' => img_picto('', 'fa-paper-plane', 'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'tools',
			'leftmenu' => 'diffusion',
			'url' => '/diffusion/diffusion_list.php',
			'langs' => 'diffusion@diffusion',
			'position' => 1001,
			'enabled' => 'isModEnabled(\'diffusion\')',
			'perms' => '$user->hasRight(\'diffusion\', \'diffusiondoc\', \'read\') || $user->hasRight(\'diffusion\', \'diffusion\', \'read\') || $user->hasRight(\'diffusion\', \'read\')',
			'target' => '',
			'user' => 2,
			'object' => 'Diffusion',
		);
		/* END MODULEBUILDER LEFTMENU DIFFUSION */
		/* BEGIN MODULEBUILDER LEFTMENU DIFFUSION */
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=diffusion',
			'type' => 'left',
			'titre' => 'ListDiffusions',
			'mainmenu' => 'tools',
			'leftmenu' => 'diffusion_diffusion_list',
			'url' => '/diffusion/diffusion_list.php',
			'langs' => 'diffusion@diffusion',
			'position' => 1003,
			'enabled' => 'isModEnabled(\'diffusion\')',
			'perms' => '$user->hasRight(\'diffusion\', \'diffusiondoc\', \'read\') || $user->hasRight(\'diffusion\', \'diffusion\', \'read\') || $user->hasRight(\'diffusion\', \'read\')',
			'target' => '',
			'user' => 2,
			'object' => 'Diffusion',
		);
		/* END MODULEBUILDER LEFTMENU DIFFUSION */
		/* BEGIN MODULEBUILDER LEFTMENU DIFFUSION */
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=diffusion',
			'type' => 'left',
			'titre' => 'NewDiffusion',
			'mainmenu' => 'tools',
			'leftmenu' => 'diffusion_diffusion_new',
			'url' => '/diffusion/diffusion_card.php?action=create',
			'langs' => 'diffusion@diffusion',
			'position' => 1002,
			'enabled' => 'isModEnabled(\'diffusion\')',
			'perms' => '$user->hasRight(\'diffusion\', \'diffusiondoc\', \'write\') || $user->hasRight(\'diffusion\', \'diffusion\', \'write\') || $user->hasRight(\'diffusion\', \'write\')',
			'target' => '',
			'user' => 2,
			'object' => 'Diffusion',
		);
		/* END MODULEBUILDER LEFTMENU DIFFUSION */

		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */
		/*
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=diffusion',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',                          // This is a Left menu entry
			'titre' => 'Diffusion',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle paddingright"'),
			'mainmenu' => 'diffusion',
			'leftmenu' => 'diffusion',
			'url' => '/diffusion/diffusionindex.php',
			'langs' => 'diffusion@diffusion',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("diffusion")', // Define condition to show or hide menu entry. Use 'isModEnabled("diffusion")' if entry must be visible if module is enabled.
			'perms' => '$user->hasRight("diffusion", "diffusiondoc", "read") || $user->hasRight("diffusion", "diffusion", "read") || $user->hasRight("diffusion", "read")',
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object' => 'Diffusion'
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=diffusion,fk_leftmenu=diffusion',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'New_Diffusion',
			'mainmenu' => 'diffusion',
			'leftmenu' => 'diffusion_diffusion_new',
			'url' => '/diffusion/diffusion_card.php?action=create',
			'langs' => 'diffusion@diffusion',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("diffusion")', // Define condition to show or hide menu entry. Use 'isModEnabled("diffusion")' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("diffusion", "diffusiondoc", "write") || $user->hasRight("diffusion", "diffusion", "write") || $user->hasRight("diffusion", "write")'
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object' => 'Diffusion'
		);
		$this->menu[$r++]=array(
			'fk_menu' => 'fk_mainmenu=diffusion,fk_leftmenu=diffusion',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'List_Diffusion',
			'mainmenu' => 'diffusion',
			'leftmenu' => 'diffusion_diffusion_list',
			'url' => '/diffusion/diffusion_list.php',
			'langs' => 'diffusion@diffusion',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("diffusion")', // Define condition to show or hide menu entry. Use 'isModEnabled("diffusion")' if entry must be visible if module is enabled.
			'perms' => '$user->hasRight("diffusion", "diffusiondoc", "read") || $user->hasRight("diffusion", "diffusion", "read") || $user->hasRight("diffusion", "read")'
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
			'object' => 'Diffusion'
		);
		*/
		/* END MODULEBUILDER LEFTMENU MYOBJECT */


		// Exports profiles provided by this module
		$r = 0;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		/*
		$langs->load("diffusion@diffusion");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'DiffusionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = $this->picto;
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'Diffusion'; $keyforclassfile='/diffusion/class/diffusion.class.php'; $keyforelement='diffusion@diffusion';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'DiffusionLine'; $keyforclassfile='/diffusion/class/diffusion.class.php'; $keyforelement='diffusionline@diffusion'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='diffusion'; $keyforaliasextra='extra'; $keyforelement='diffusion@diffusion';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='diffusionline'; $keyforaliasextra='extraline'; $keyforelement='diffusionline@diffusion';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('diffusionline' => array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field' => '...');
		//$this->export_examplevalues_array[$r] = array('t.field' => 'Example');
		//$this->export_help_array[$r] = array('t.field' => 'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'diffusion as t';
		//$this->export_sql_end[$r]  .=' LEFT JOIN '.MAIN_DB_PREFIX.'diffusion_line as tl ON tl.fk_diffusion = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('diffusion').')';
		$r++; */
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 0;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/*
		$langs->load("diffusion@diffusion");
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'DiffusionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r] = $this->picto;
		$this->import_tables_array[$r] = array('t' => MAIN_DB_PREFIX.'diffusion', 'extra' => MAIN_DB_PREFIX.'diffusion_extrafields');
		$this->import_tables_creator_array[$r] = array('t' => 'fk_user_author'); // Fields to store import user id
		$import_sample = array();
		$keyforclass = 'Diffusion'; $keyforclassfile='/diffusion/class/diffusion.class.php'; $keyforelement='diffusion@diffusion';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinimport.inc.php';
		$import_extrafield_sample = array();
		$keyforselect='diffusion'; $keyforaliasextra='extra'; $keyforelement='diffusion@diffusion';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'diffusion');
		$this->import_regex_array[$r] = array();
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('t.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			't.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(!getDolGlobalString('DIFFUSION_MYOBJECT_ADDON') ? 'mod_diffusion_standard' : getDolGlobalString('DIFFUSION_MYOBJECT_ADDON')),
				'path'=>"/core/modules/diffusion/".(!getDolGlobalString('DIFFUSION_MYOBJECT_ADDON') ? 'mod_diffusion_standard' : getDolGlobalString('DIFFUSION_MYOBJECT_ADDON')).'.php',
				'classobject'=>'Diffusion',
				'pathobject'=>'/diffusion/class/diffusion.class.php',
			),
			't.fk_soc' => array('rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'),
			't.fk_user_valid' => array('rule' => 'fetchidfromref', 'file' => '/user/class/user.class.php', 'class' => 'User', 'method' => 'fetch', 'element' => 'user'),
			't.fk_mode_reglement' => array('rule' => 'fetchidfromcodeorlabel', 'file' => '/compta/paiement/class/cpaiement.class.php', 'class' => 'Cpaiement', 'method' => 'fetch', 'element' => 'cpayment'),
		);
		$this->import_run_sql_after_array[$r] = array();
		$r++; */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          	1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Create tables of module at module activation
		//$result = $this->_load_tables('/install/mysql/', 'diffusion');
		$result = $this->_load_tables('/diffusion/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result0=$extrafields->addExtraField('diffusion_separator1', "Separator 1", 'separator', 1,  0, 'thirdparty',   0, 0, '', array('options'=>array(1=>1)), 1, '', 1, 0, '', '', 'diffusion@diffusion', 'isModEnabled("diffusion")');
		//$result1=$extrafields->addExtraField('diffusion_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', -1, 0, '', '', 'diffusion@diffusion', 'isModEnabled("diffusion")');
		//$result2=$extrafields->addExtraField('diffusion_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', -1, 0, '', '', 'diffusion@diffusion', 'isModEnabled("diffusion")');
		//$result3=$extrafields->addExtraField('diffusion_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', -1, 0, '', '', 'diffusion@diffusion', 'isModEnabled("diffusion")');
		//$result4=$extrafields->addExtraField('diffusion_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', -1, 0, '', '', 'diffusion@diffusion', 'isModEnabled("diffusion")');
		//$result5=$extrafields->addExtraField('diffusion_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', -1, 0, '', '', 'diffusion@diffusion', 'isModEnabled("diffusion")');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('diffusion');
		$myTmpObjects = array();
		$myTmpObjects['Diffusion'] = array('includerefgeneration' => 1, 'includedocgeneration' => 1);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_diffusions.odt';
				$dirodt = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_diffusions.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, '0', 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		$result = $this->_init($sql, $options);
		if ($result > 0) {
			dolibarr_set_const($this->db, 'MAIN_MODULE_DIFFUSION', '1', 'chaine', 0, '', $conf->entity);

			dol_include_once('/diffusion/class/actions_diffusion.class.php');

			$externalmodule = json_decode((string) ($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING ?? ''), true);
			if (!is_array($externalmodule)) {
				$externalmodule = array();
			}

			$params = class_exists('ActionsDiffusion') ? ActionsDiffusion::getMulticompanySharingDefinition() : array();
			if (!empty($params)) {
				$externalmodule = array_merge($externalmodule, $params);
				$jsonformat = json_encode($externalmodule);
				dolibarr_set_const($this->db, 'MULTICOMPANY_EXTERNAL_MODULES_SHARING', $jsonformat, 'chaine', 0, '', $conf->entity);
			}

			$resultregister = $this->registerDiffusionActionTriggers();
			if ($resultregister < 0) {
				return -1;
			}
		}

		return $result;
	}

	/**
	 * Register DIFFUSION_* action triggers into llx_c_action_trigger.
	 *
	 * @return int<-1,1> Return 1 if OK, <0 if KO
	 */
	private function registerDiffusionActionTriggers()
	{
		global $langs;

		$langs->load('diffusion@diffusion');

		$triggers = array(
			'DIFFUSION_CREATE' => array('DiffusionTriggerLabelCreate', 'DiffusionTriggerDescCreate', 2000),
			'DIFFUSION_VALIDATE' => array('DiffusionTriggerLabelValidate', 'DiffusionTriggerDescValidate', 2001),
			'DIFFUSION_SENDMAIL' => array('DiffusionTriggerLabelSendMail', 'DiffusionTriggerDescSendMail', 2002),
			'DIFFUSION_SETDIFFUSED' => array('DiffusionTriggerLabelSetDiffused', 'DiffusionTriggerDescSetDiffused', 2003),
			'DIFFUSION_BACKTODRAFT' => array('DiffusionTriggerLabelBackToDraft', 'DiffusionTriggerDescBackToDraft', 2004),
			'DIFFUSION_DELETE' => array('DiffusionTriggerLabelDelete', 'DiffusionTriggerDescDelete', 2005),
		);

		foreach ($triggers as $code => $triggerconf) {
			$label = $this->db->escape($langs->transnoentities($triggerconf[0]));
			$description = $this->db->escape($langs->transnoentities($triggerconf[1]));

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_action_trigger (code, label, description, elementtype, rang)";
			$sql .= " SELECT '".$this->db->escape($code)."', '".$label."', '".$description."', 'diffusion@diffusion', ".((int) $triggerconf[2]);
			$sql .= " FROM DUAL";
			$sql .= " WHERE NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."c_action_trigger WHERE code = '".$this->db->escape($code)."')";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				return -1;
			}

			$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."c_action_trigger";
			$sqlupdate .= " SET label = '".$label."', description = '".$description."', elementtype = 'diffusion@diffusion', rang = ".((int) $triggerconf[2]);
			$sqlupdate .= " WHERE code = '".$this->db->escape($code)."'";

			$resql = $this->db->query($sqlupdate);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				return -2;
			}
		}

		return 1;
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		global $conf;

		dol_include_once('/diffusion/class/actions_diffusion.class.php');

		$externalmodule = json_decode((string) ($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING ?? ''), true);
		if (!is_array($externalmodule)) {
			$externalmodule = array();
		}

		$sharingKey = class_exists('ActionsDiffusion') ? ActionsDiffusion::MULTICOMPANY_SHARING_ROOT_KEY : 'diffusion';
		unset($externalmodule[$sharingKey]);

		$jsonformat = json_encode($externalmodule);
		dolibarr_set_const($this->db, 'MULTICOMPANY_EXTERNAL_MODULES_SHARING', $jsonformat, 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($this->db, 'MAIN_MODULE_DIFFUSION', $conf->entity);

		$sql = array();
		return $this->_remove($sql, $options);
	}
}
