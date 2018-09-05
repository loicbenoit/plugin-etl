<?php
use \retl\system\libraries\Paths;

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
 * Handles view files related logic as part of a solution to add some MVC logic into GLPI.
 */
class PluginEtlView
{
   const VIEWS_DIR = 'views';

   /**
    * Run a view script by name and pass provided data.
    *
    * Note: The keys of array $data will become variables in the view.
    *       Given:
    *          $data = [
    *             'foo' => 'foo value',
    *             'bar' => ['a', 'b', 'c'],
    *          ];
    *       
    *       A view script will have the following variables in scope (with associated value):
    *          $foo
    *          $bar
    * 
    * @param   string   $uri  A file name with extension, as a relative path to
    *                         directory "myplugin/inc/views/".
    * @param   array   $data  An associative list of variable name to value.
    *                         SECURITY: Make sure $data contains trusted values.
    *
    * @return return A string representing the output of the loaded script.
    */
   public static function load(string $uri, array $data = []) {
      $path = self::getViewPath($uri);
      
      if( ! is_readable($path)){
         throw new Exception('File not found or not readable for path: '.$path);
      }
      
      extract($data);
      
      ob_start();
      try {
         include($path);
      } finally {
         $retval = ob_get_clean();
      }
      
      return $retval;
   }

   /**
    * Get the plugin setup/configuration object
    *
    * Relies on a global variable, but could be implemented another way.
    *
    * @return Object of class PluginETLSetupLogic
    */
   protected static function getPluginSetup() {
      global $PLUGIN_ETL_SINGLETON_SETUP_LOGIC;
      return $PLUGIN_ETL_SINGLETON_SETUP_LOGIC;
   }

   /**
    * Get the absolute path to the plugin directory
    *
    * @return string
    */
   public static function getPluginBasePath() {
      return self::getPluginSetup()->getPluginIncPath();
   }

   /**
    * Get the absolute path to the directory that contains all views scripts.
    *
    * @param   string   $uri  A file name with extension, as a relative path to
    *                         directory "myplugin/inc/views/".
    * 
    * @return string
    */
   public static function getViewPath(string $uri = '') {
      return Paths::implodePath([
         self::getPluginBasePath(),
         self::VIEWS_DIR,
         $uri
      ]);
   }


   /**
    * Generic logic for converting an item of any type into an HTML string.
    *
    * - String: Escape string and convert newlines to <br/>
    * - Object: Try the following scenarios in order.
    *    1- If method exists "toHtml": Use return value, as provided (no additionnal escaping).
    *    2- If method exists "__toString": Handle as if string. See above.
    *    3- Else: Use function var_export() and escape resulting string.
    * - Array: For each array item, display the key (escaped) and pass the item to toGenericHtml
    *          This method is recursive. However, it will NOT stop when arrays contain cycles
    *          (memory references that refer to previously visited items). Make sure your arrays
    *          don't contain recursive memory references (i.e.: the usual case).
    * - Other: Use function var_export() and escape resulting string.
    * 
    * Usage note: You must provide the external group tag. For example, if $item_open = '<li>',
    *             then you MUST wrap the output of this function in a '<ul>' or '<ol>' tag.
    *             The group wrapper in only added when recursing, allowing the external group tag
    *             to use different CSS classes and HTML attributes than internal groups.
    * 
    * Example 1: A list can contain a recursive list identified by ***.
    * 
    *    group_tag: '<ul class="group">'
    *    item_tag: '<li class="item">'
    *    category_tag: '<span class="category">'
    *    
    *    <ul class="external-group" data-external-group-id="123">
    *       <li class="item">
    *          <span class="category"></span>
    *          *** <ul class="group">
    *          ***   <li class="item">
    *          ***      <span class="category"></span>
    *          ***      ... Item content ...
    *          ***   </li>
    *          *** </ul>
    *       </li>
    *    </ul>
    *
    * Example 2: A div can contain a recursive div identified by ***.
    * 
    *    group_tag: '<div class="group">'
    *    item_tag: '<div class="item">'
    *    category_tag: '<span class="category">'
    *    
    *    <div class="external-group" data-external-group-id="123">
    *       <div class="item">
    *          <span class="category"></span>
    *          *** <div class="group">
    *          ***   <div class="item">
    *          ***      <span class="category"></span>
    *          ***      ... Item content ...
    *          ***   </div>
    *          *** </div>
    *       </div>
    *    </div>
    * 
    * @param   mixed    $item       A string, an object, an array, etc. to convert to string.
    * @param   string   $item_tag   (Optional) The name of the HTML block element to wrap each item.
    *                               (Default: "li") The HTML tag MUST be able to contain itself
    *                               such as "<li> <li></li> </li>" or "<div> <div></div> </div>".
    * @param   string   $group_tag  (Optional) The tag label of the HTML block that wraps sub-groups
    *                               of items. (Default "ul"). Must be able to contain itself.
    * @param   string   $category_tag  (Optional) The tag label of array keys when recursing
    *                                  into arrays.
    *
    * @return return An HTML string representation of the item
    */
   public static function toGenericHtml(
      $item,
      string $item_tag = 'li',
      string $group_tag = 'ul',
      string $category_tag = ''
   ) {
      $html = [];

      $html[] = '<'.$item_tag.'>';
      
         if(is_object($item) && method_exists($item, 'toHtml')) {
               $html[] = $item->toHtml();
         
         } elseif(is_string($item)) {
            $html[] = nl2br(htmlentities($item));
         
         } elseif(is_object($item) && method_exists($item, '__toString')) {
            $html[] = nl2br(htmlentities($item));
            
         } elseif(is_array($item)) {
            foreach($item as $category => $subitem) {
               $html[] = strlen($category_tag) > 0 ? '<'.$category_tag.'>' : '';
                  $html[] = htmlentities($category).': ';
               $html[] = strlen($category_tag) > 0 ? '</'.$category_tag.'>' : '';
               $html[] = '<'.$group_tag.'>';
                  $html[] = self::toGenericHtml($subitem);
               $html[] = '</'.$group_tag.'>';
            }
         
         } else {
            $html[] = '<pre>';
               $html[] = htmlentities(var_export($item, true));
            $html[] = '</pre>';
         }

      $html[] = '</'.$item_tag.'>';
      
      return implode('', $html);
   }


   /**
    * Generic logic for converting an item of any type into an HTML table.
    *
    * - String: Escape string and convert newlines to <br/>
    * - Object: Try the following scenarios in order.
    *    1- If method exists "toHtml": Use return value, as provided (no additionnal escaping).
    *    2- If method exists "__toString": Handle as if string. See above.
    *    3- Else: Use function var_export() and escape resulting string.
    * - Array: For each array item, display the key (escaped) and pass the item to toGenericHtmlTable
    *          This method is recursive. However, it will NOT stop when arrays contain cycles
    *          (memory references that refer to previously visited items). Make sure your arrays
    *          don't contain recursive memory references (i.e.: the usual case).
    * - Other: Use function var_export() and escape resulting string.
    * 
    * Usage note: You must provide the external table openning and closing tags. Row and cell tags
    *             are added automatically.
    * 
    * @param   mixed    $item       A string, an object, an array, etc. to convert to string.
    *
    * @return return An HTML string representation of the item
    */
   public static function toGenericHtmlTable($item) {
      $html = [];
   
      if(is_object($item) && method_exists($item, 'toHtml')) {
         $html[] = '<tr>';
            $html[] = '<td>';
               $html[] = $item->toHtml();
            $html[] = '</td>';
         $html[] = '</tr>';
         
      } elseif(is_string($item)) {
         $html[] = '<tr>';
            $html[] = '<td>';
               $html[] = nl2br(htmlentities($item));
            $html[] = '</td>';
         $html[] = '</tr>';
      
      } elseif(is_object($item) && method_exists($item, '__toString')) {
         $html[] = '<tr>';
            $html[] = '<td>';
               $html[] = nl2br(htmlentities($item));
            $html[] = '</td>';
         $html[] = '</tr>';
         
      } elseif(is_array($item)) {
         foreach($item as $category => $subitem) {
            $html[] = '<tr>';
               $html[] = '<td>';
                  $html[] = htmlentities($category).': ';
               $html[] = '</td>';
               $html[] = '<td>';
                  $html[] = '<table class="plugin-etl__section-error__recursing">';
                     $html[] = '<tbody>';
                        $html[] = self::toGenericHtmlTable($subitem);
                        $html[] = '</tbody>';
                     $html[] = '</table>';
               $html[] = '</td>';
            $html[] = '</tr>';
         }
      
      } else {
         $html[] = '<tr>';
            $html[] = '<td>';
               $html[] = '<pre>';
                  $html[] = htmlentities(var_export($item, true));
               $html[] = '</pre>';
            $html[] = '</td>';
         $html[] = '</tr>';
      }
      
      return implode('', $html);
   }

}