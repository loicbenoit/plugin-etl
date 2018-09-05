<?php
/*
 -------------------------------------------------------------------------
 Plugin-ETL: Import and export data with the RETL library and GLPI API.
 --------------------------------------------------------------------------
 @package   plugin-etl
 @author    Ville de Montréal
 @link      https://github.com/VilledeMontreal/plugin-etl
 @link      http://www.glpi-project.org/
 @since     2018
 --------------------------------------------------------------------------
*/


//define('PLUGIN_ETL_VERSION', '0.0.1');

// Class that contains standardized logic for configuring plugins.
require (dirname(__FILE__).'/inc/setuplogic.class.php');


/**
 * Get the name and the version of the plugin
 * REQUIRED by GLPI
 *
 * @return array
 */
function plugin_version_etl() {
   return [
      'name'           => 'ETL', //Class name in camel case
      'version'        => '0.0.1', //Semantic versionning as Major.Minor.Patch
      'author'         => 'Ville de Montréal',
      'license'        => 'GPLv3',
      'homepage'       => 'https://github.com/VilledeMontreal/plugin-etl',
      'requirements'   => [
         'glpi' => [
            'min' => '9.3',
            'dev' => true
         ]
      ]
   ];
}


/**
 * Init hooks of the plugin.
 * REQUIRED by GLPI
 *
 * @return void
 */
function plugin_init_etl() {
   global $PLUGIN_HOOKS;
   
   // Logging example
   //Toolbox::logDebug('Initiating my awesome plugin for most requests received by GLPI!');   
   
   // Activate CSRF protection for this plugin.
   // Works with functions such as Html::closeForm() that include the CSRF token in forms.
   $PLUGIN_HOOKS['csrf_compliant']['etl'] = true;

   // Get an object that represents any GLPI plugin.
   $plugin = new Plugin();
   
   // Set some assumptions for configuring the plugin automatically.
   $setup_logic = new PluginETLSetupLogic(
      // Official plugin name (usually in camel case)
      plugin_version_etl()['name'],
      
      // When using your plugin name in a class name, should it be UCFirst?
      //    UCFirst means: All lowercase except the first letter, "Ucfirst" instead of "UCFirst".
      //
      //    TRUE: UCFirst, the GLPI standard. For example, "Myfoo" in : PluginMyfooMyclass
      //          Therefore, classes don't use the same capitalisation as the official plugin name.
      //    FALSE: Camel case, because KISS. For example, "MyFoo" in : PluginMyFooMyclass
      //          Therefore, classes use the exact same capitalisation as the official plugin name.
      //
      // WARNING: This impacts class autoloading. Classes called from outside of the plugin
      //          may have to follow the standard GLPI naming convention of UCFirst.
      false, 
      
      //(Optional) List of mandatory editable sub-paths relative to "/files/_plugins/my-plugin".
      // Note: Paths will be created when missing.
      [
         'jobs',
         'plans',
      ]
   );
   
   // Only activate plugin if: Installed + active + authenticated context.
   if($plugin->isInstalled($setup_logic->getLName())
       && $plugin->isActivated($setup_logic->getLName())
       && Session::getLoginUserID()) {

      $setup_logic->setupAutoloading();
      $setup_logic->setupEditablePaths();
      $setup_logic->addToMainMenu('tools'); //REM: You must create a class like PluginETLMenu
      //$setup_logic->addProfilePermissionTab(); //REM: You must create classes: Profile and Model.
      $setup_logic->addCss('etl.css');
      $setup_logic->addJs('etl.js');
   };

   
   // Make the plugin logic singleton available globally.
   // This plugin can only have one configuration per GLPI installation.
   // Note: Declaring the global variable should be done last, because initiating the plugin should
   //       not rely on code that uses this global variable. This limitation should help prevent
   //       nasty bugs and make the code easier to maintain while allowing a global variable.
   // Use case: Get the plugin's configs in static methods such as View::load.
   $GLOBALS['PLUGIN_ETL_SINGLETON_SETUP_LOGIC'] = $setup_logic;
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded by GLPI
 *
 * @return boolean
 */
function plugin_etl_check_prerequisites() {
   $min_glpi_version = plugin_version_etl()['requirements']['glpi']['min'];
   
   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION, $min_glpi_version, 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', $min_glpi_version);
      } else {
         echo "This plugin requires GLPI >= ".$min_glpi_version;
      }
      return false;
   }
   return true;
}

/**
 * Check configuration process
 * OPTIONNAL, but recommanded by GLPI
 * 
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_etl_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      echo __('Installed / not configured', 'etl');
   }
   return false;
}

