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
/*
 --------------------------------------------------------------------------
 Copied and adapted from GLPI plugin GenericObject
 Link: https://github.com/pluginsGLPI/genericobject
 --------------------------------------------------------------------------
*/
/**
 * Class autoloading combining GLPI style and PSR4.
 *    - GLPI style for compatibility with GLPI (for data models and for extending GLPI classes).
 *    - PSR4 used internally by the plugin only, for better code organisation, whenever possible.
 *
 */
class PluginETLAutoloader
{
   protected $plugin_name = '';
   protected $paths = [];
   
   /**
    * Constructor
    * 
    * @param   array   $paths          The list of absolute paths were to find class files. 
    * @param   string  $plugin_name    The plugin name all in lower case
    * @return void
    */
   public function __construct($paths = null, string $plugin_name = '') {
      if (null !== $paths) {
         $this->setPaths($paths);
      }
      
      if(empty($plugin_name)) {
         throw new Exception('Usage: Parameter "plugin_name" must be a non-empty string.');
      }
      $this->plugin_name = $plugin_name;
   }

   public function setPaths($paths) {
      if (!is_array($paths) && !($paths instanceof \Traversable)) {
         throw new \InvalidArgumentException();
      }

      foreach ($paths as $path) {
         if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
         }
      }
      return $this;
   }

   /**
    * Split class name into the 3 expected parts or return false.
    *
    * GLPI class names have 3 or 4 parts:
    *    Plugin + Plugin name + Class name
    *    
    * Where "Plugin name" must match $this->plugin_name.
    *
    * @param   string   $class_name A class name that follows GLPI conventions
    * @return  array    Class name parts or false.
    */
   public function splitClassName($class_name) {
      preg_match('/(Plugin)('.$this->plugin_name.')([A-Z]\w+)/', $class_name, $matches);

      if (count($matches) < 4) {
         return false; //To let autoloading handle errors.
      }
      
      return [
         'plugin_prefix' => $matches[1],
         'plugin_name' => $matches[2],
         'class_name' => $matches[3],
         'full_class_name' => $class_name,
      ];
   }

   public function autoload($class_name) {
      //Toolbox::logDebug('Calling autoload for: '.$class_name);
      $methods = [
         'glpiAutoload',
         'almostPsr4Autoload',
      ];
      
      foreach ($methods as $method) {
         foreach ($this->paths as $base_path) {
            $result = $this->$method($class_name, $base_path);
            if($result) {
               return $result;
            }
         }
      }
      
      return false;
   }

   /*
    * Usual autoloading function for GLPI.
    * Adapted from plugins DataInjection and GenericObjects.
    *
    * @param   string   $class_name    The class name with leading namespace
    *                                  Namespace should match the plugin's name
    * @param   string   $path          The basepath where to look for the file.
    */
   protected function glpiAutoload(string $class_name, string $path) {
      $name_parts = $this->splitClassName($class_name);
      
      if(is_array($name_parts)
         && $name_parts['plugin_name'] === $this->plugin_name
      ) {
         $filename = implode(".", [
            strtolower($name_parts['class_name']),
            "class",
            "php"
         ]);
   
         $file_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
         if (file_exists($file_path)) {
            return include($file_path);
         } else {
            //Toolbox::logDebug('Autoload failed to find file: '.$file_path);
            return false;
         }

      } else {
         return false;
      }
   }

   /*
    * Almost PSR-4 compliant autoloading function.
    * Adapted from: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
    *
    * @param   string   $class_name    The class name with leading namespace
    *                                  Namespace should match the plugin's name
    * @param   string   $path          The basepath where to look for the file.
    */
   protected function almostPsr4Autoload(string $class_name, string $path) {
      // project-specific namespace prefix
      $prefix = 'Plugin'.$this->plugin_name.'\\';
  
      // does the class use the namespace prefix?
      $len = strlen($prefix);
      if (strncmp($prefix, $class_name, $len) !== 0) {
          // no, move to the next registered autoloader
          return false;
      }
  
      // get the class name without namespace
      $relative_class = substr($class_name, $len);
  
      // get "base_path/class_name.php"
      $file = rtrim($path, DIRECTORY_SEPARATOR)
         .DIRECTORY_SEPARATOR
         .str_replace('\\', '/', $relative_class)
         .'.php';
  
      if (file_exists($file)) {
          require $file;
      } else {
         return false;
      }
   }
   
   /**
    * Register this autolaoder in SPL PHP library
    *
    */
   public function register() {
      spl_autoload_register([$this, 'autoload']);
   }
}
