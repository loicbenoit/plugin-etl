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

 
class PluginETLSetupLogic
{
   protected $plugin;
   protected $name;
   protected $use_ucfirst_naming = false;
   protected $mandatory_editable_subdirectories = [];
   
   const GLPI_CLASS_NAME_PREFIX = 'Plugin';
   const PLUGINS_DIR = 'plugins';
   const INC_DIR = 'inc';
   const HELPERS_DIR = 'helpers';
   const AUTOLOADER_FILE_NAME = 'autoload.class.php';
   const AUTOLOADER_CLASS_NAME_TEMPLATE = 'Plugin%sAutoloader';
   const DFT_MENU_CLASS_NAME = 'Menu';
   
   /**
    * Constructor
    *
    * @param   string   $plugin_name         The plugin name with capital letters, no spaces
    *                                        (Same as "NAME" variable in plugin installation
    *                                        template).
    * @param   bool     $use_ucfirst_naming  (Optional) Is plugin name strictly ucfirst?
    *                                        Ucfirst: All lower case except the first character.
    *                                        Default: false.
    *                                        Usual GLPI convention is true.
    */
   function __construct(
      string $plugin_name,
      $use_ucfirst_naming = false,
      array $mandatory_editable_subdirectories = []
   ) {
      $this->plugin = new Plugin();
      $this->use_ucfirst_naming = (bool) $use_ucfirst_naming;
      $this->name = $this->use_ucfirst_naming
         ? $this->getUCFirstName($plugin_name)
         : $plugin_name;
      $this->mandatory_editable_subdirectories = $mandatory_editable_subdirectories;
   }


   /**
    * Get the plugin name as defined.
    *
    * IMPORTANT: Must be a name suitable for use as part of a PHP class name.
    *
    * @return string
    */
   public function getName() {
      return $this->name;
   }

   /**
    * Utility method to get the lower case version of the plugin name.
    *
    * @return string
    */
   public function getLName() {
      return strtolower($this->getName());
   }

   /**
    * Utility method to get the upper case version of the plugin name.
    *
    * @return string
    */
   public function getUName() {
      return strtoupper($this->getName());
   }

   /**
    * Utility method to get the upper case version of the plugin name.
    *
    * @param   string   $name (Optional) The name to convert or $this->getName()
    * @return string
    */
   public function getUCFirstName($name = NULL) {
      $name = is_string($name)
         ? $name
         : $this->getName();

      return ucfirst(strtolower($name));
   }

   /**
    * Get the class name expected by class autoloading in the context of this plugin.
    *
    * Example of GLPI conventions regarding class names:
    *    - Desired class name without prefix: PathHelper
    *    - Resulting autoloading name for plugin called "ETL": PluginETLPathHelper
    *    - Expected file name: pathhelper.class.php
    *
    * Note: A general expectation regarding plugin names: Only the first letter should be
    *       capiatlised. We disagree, so ETL autoloader allows plugin names to be camel cased.
    *       Digress at your own risks. The safest path is to use the GLPI convention.
    *
    * @param   string   $class_name    The class name (usually camel cased)
    * @return string
    * @todo Thoroughly test
    */
   public function getAutoloadingName(string $class_name) {
      if(strlen($class_name) < 1) {
         throw new Exception('Usage: Parameter "class_name" must be a non-empty string.');
      }
      return self::GLPI_CLASS_NAME_PREFIX.$this->getName().$class_name;
   }

   /**
    * Get the file name usually expected by GLPI for any given class.
    *
    * @param   string   $class_name (Optional) The class name, defaults to getLName().
    * @return string
    * @todo Thoroughly test
    */
   public function getClassFileName(string $class_name = NULL) {
      $class_name = is_string($class_name) && strlen($class_name)
         ? $class_name
         : $this->getLName();
      
      return $this->removeClassNamePrefix($class_name.'.class.php');
   }

   /**
    * Get the path of the directory were this plugin can officially create and edit files.
    * Note: A GLPI convention. Not enforced, but recommended.
    *
    * @return string
    */
   public function getOwnEditableBasePath() {
      return implode(DIRECTORY_SEPARATOR, [
         $this->getEditableBasePathForAllPlugins(),
         $this->getLName()
      ]);
   }

   /**
    * Get the path of the parent directory that contains all directories that plugins can edit.
    * Note: A GLPI convention. Not enforced, but recommended.
    *
    * @return string
    */
   public function getEditableBasePathForAllPlugins() {
      return GLPI_PLUGIN_DOC_DIR;
   }

   /**
    * Get the base path of this plugin.
    *
    * @param   string/array   $relative_path A relative path to append expressed as a string
    *                                        or list of segments to implode into a path string.
    *
    * @return string
    */
   public function getPluginBasePath($relative_path = NULL) {
      $segments = [
         GLPI_ROOT,
         self::PLUGINS_DIR,
         $this->getLName()
      ];
      
      if(is_string($relative_path)) {
         $segments[] = $relative_path;
      } else if(is_array($relative_path)) {
         $segments = array_merge($segments, $relative_path);
      }
      
      return implode(DIRECTORY_SEPARATOR, $segments);
   }

   /**
    * Get the base path of the "inc" directory of this plugin.
    *
    * @param   string/array   $relative_path A relative path to append expressed as a string
    *                                        or list of segments to implode into a path string.
    *
    * @return string
    */
   public function getPluginIncPath($relative_path = NULL) {
      $segments = [
         $this->getPluginBasePath(),
         self::INC_DIR
      ];
      
      if(is_string($relative_path)) {
         $segments[] = $relative_path;
      } else if(is_array($relative_path)) {
         $segments = array_merge($segments, $relative_path);
      }
      
      return implode(DIRECTORY_SEPARATOR, $segments);
   }
   
   /**
    * Remove the class name prefix that GLPI usually expects.
    *
    * Note: The prefix is a namespace. Present were required to avoid class name collisions,
    *       often removed when not required, such as in class file names.
    *
    * @param   string   $class_name The class name
    * @return  string
    * @todo Thoroughly test
    */
   public function removeClassNamePrefix($class_name) {
      // Nothing to do if prefix isn't there.
      if(strpos($class_name, self::GLPI_CLASS_NAME_PREFIX) !== 0) {
         return $class_name;
      }

      $retval = substr($class_name, strlen(self::GLPI_CLASS_NAME_PREFIX));
      if( ! is_string($retval)) {
         throw new Exception('substr() error');
      }
      
      return $retval;
   }
   
   /**
    * Add possibility to configure permissions on a profile basis for actions related to this plugin.
    *
    * Specific list of possible actions is defined somewhere else (TBD).
    * See Administration/Profiles/<profile i>
    *
    * @return boolean
    * @TODO: Code class "/inc/profile.class.php". Autoloader expects class name: "PluginETLProfile".
    *        That class is required to make permission tab work.
    */
   public function addProfilePermissionTab() {
      Plugin::registerClass(
          'PluginETLProfile',
          [
            'addtabon' => ['Profile'],
          ]
      );
   }

   /**
    * Add plugin to main menu
    *
    * @param   string   $menu_category (Optional) In which GLPI menu should your item be added?
    *                                  Common: 'config', 'tools', 'assets'
    *                                  See file: /inc/html.class.php
    * @param   string   $menu_class    (Optional) Name of class with menu configuration logic.
    *                                  Defaults to: Plugin + Plugin name + Menu
    *
    * @return void
    */
   public function addToMainMenu(string $menu_category = 'config', string $menu_class = NULL) {
      global $PLUGIN_HOOKS;
      
      $menu_class = is_string($menu_class) && strlen($menu_class)
         ? $menu_class
         : $this->getAutoloadingName(self::DFT_MENU_CLASS_NAME);
      
      // Adapted from DataInjection
      $PLUGIN_HOOKS['menu_toadd'][$this->getLName()] = [$menu_category  => $menu_class];
   }

   /**
    * Make sure editable paths for this plugin exists under /glpi/files/_plugins/
    * (as mandated by GLPI for dynamically editing files)
    * 
    * Additionnaly, make sure required subdirectories exists.
    * 
    * See GLPI documentation:
    * http://glpi-developer-documentation.readthedocs.io/en/master/sourcecode.html#file-hierarchy-system
    *
    * @return boolean
    * @todo test
    */
   public function setupEditablePaths() {
      
      if ( ! $this->plugin->isInstalled($this->getLName())) {
         //Nothing to do if plugin is not installed.
         //Note: However tempting, do NOT remove paths here. It would be an unwanted side effect
         //      for a function whose role is only to setup paths, but not to remove them.
         //      Removing paths should only be while uninstalling or in a dedicated function.
         return;
      }
      
      //Create editable basepath, if missing.
      if ( ! is_writable($this->getOwnEditableBasePath())) {
         Paths::makeDirOrDie($this->getOwnEditableBasePath());
      }
      
      //Create mandatory editable sub directories, if missing.
      foreach($this->getMandatoryEditableSubdirectories() as $dir) {
         $path = $this->getOwnEditableBasePath().DIRECTORY_SEPARATOR.$dir;
         if ( ! is_writable($path)) {
            Paths::makeDirOrDie($path);
         }
      };
   }

    /**
    * Get the list of mandatory editable subdirectory name.
    *
    * Note: Only contains the name or relative path of each directory.
    *       Prepend $this->getOwnEditableBasePath() to get absolute path.
    *
    * WARNING: Order directories in dependency order to avoid errors.
    *    Correct example:
    *       /jobs
    *       /jobs/data
    *       
    *    Incorrect example:
    *       /jobs/data !Error: "/jobs" does not exist
    *       /jobs
    *
    * @return array of strings
    * @TODO Fetch from a config file or something like that.
    */
   public function getMandatoryEditableSubdirectories() {
      return $this->mandatory_editable_subdirectories;
   }

    /**
    * Setup class autoloading that depends on Composer packages. 
    * 
    * @return void
    */
   public function setupComposerAutoloading() {
      require (dirname(__FILE__).'/../vendor/autoload.php');
   }

    /**
    * Setup class autoloading for this plugin based on general assumptions about GLPI plugins
    * and configurations given to the constructor of this class.
    * 
    * @return void
    */
   public function setupAutoloading() {
      $this->setupComposerAutoloading();
      require ($this->getPluginIncPath(self::AUTOLOADER_FILE_NAME));
      
      //Configure and register autoloader
      $autoloader_name = sprintf(
         self::AUTOLOADER_CLASS_NAME_TEMPLATE,
         $this->getName()
      );
      
      $autoload_paths = [
         $this->getPluginIncPath(),
      ];
      
      $autoloader = new $autoloader_name(
         $autoload_paths,
         $this->getName()
      );
      $autoloader->register();
   }

    /**
    * Add a CSS file located inside a plugin's css directory.
    * 
    * @param   string   $file_name     The css file name (with extension)
    * @param   string   $plugin_name   (Optional) The plugin name. Defaults to current plugin.
    * @param   string   $uri_pattern   (Optional) A regex pattern to match against the request URI.
    *                                  If found, then the file is added, else it is not added.
    *                                  Defaults to a pattern that looks for the current plugin.
    *                                  You MUST wrap your pattern with pattern delimiters.
    * 
    * @return void
    */
   public function addCss(
      string $file_name,
      string $plugin_name = NULL,
      string $uri_pattern = NULL
   ) {
      global $PLUGIN_HOOKS;
      
      $plugin_name = is_string($plugin_name) ? $plugin_name : $this->getLName();
      $uri_pattern = is_string($uri_pattern) ? $uri_pattern : '%/plugins/'.$plugin_name.'/%i';
      
      if(preg_match($uri_pattern, $_SERVER['REQUEST_URI']) === 1) {
         $PLUGIN_HOOKS['add_css'][$plugin_name][] = Paths::implodePath([
            'css',
            $file_name
         ]);
      }
   }

    /**
    * Add a JS file located inside a plugin's js directory.
    * 
    * @param   string   $file_name     The js file name (with extension)
    * @param   string   $plugin_name   (Optional) The plugin name. Defaults to current plugin.
    * @param   string   $uri_pattern   (Optional) A regex pattern to match against the request URI.
    *                                  If found, then the file is added, else it is not added.
    *                                  Defaults to a pattern that looks for the current plugin.
    *                                  You MUST wrap your pattern with pattern delimiters.
    * 
    * @return void
    * @todo TEST
    */
   public function addJs(
      string $file_name,
      string $plugin_name = NULL,
      string $uri_pattern = NULL
   ) {
      global $PLUGIN_HOOKS;
      
      $plugin_name = is_string($plugin_name) ? $plugin_name : $this->getLName();
      $uri_pattern = is_string($uri_pattern) ? $uri_pattern : '%/plugins/'.$plugin_name.'/%i';
      
      if(preg_match($uri_pattern, $_SERVER['REQUEST_URI']) === 1) {
         $PLUGIN_HOOKS['add_javascript'][$plugin_name][] = Paths::implodePath([
            'js',
            $file_name
         ]);
      }
   }
   
}
