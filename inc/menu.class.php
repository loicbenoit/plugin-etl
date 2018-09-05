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

/**
 * Add this plugin in GLPI main menu and configure breadcrumbs.
 *
 */

class PluginETLMenu extends CommonGLPI
{

   //static $rightname = 'plugin_etl_use';

   /**
    *
    * @todo Add translation
    */
   static function getMenuName() {
      return 'Importation CSV';
      //return __('Importation CSV', 'etl');
   }

   /**
    *
    * @todo Add permission management, breadcrumbs and all...
    */
   static function getMenuContent() {
      $target_url = '/plugins/etl/front/csvimport.form.php';

      $menu          = parent::getMenuContent();
      $menu['title'] = self::getMenuName();
      $menu['page']  = $target_url;
      
      return $menu;
   }
   
}
