<?php
namespace PluginETL\io;

use \PluginETL\errors\RecoverableException;

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
 * Handles programmatic importation of data into GLPI.
 *
 * Note: This class extend the GLPI API class, for there is no library for importing itemtypes
 *       and plugin DataInjection currently has a bug.
 *       See api.class.php::createItems() -> To create a single item by item type.
 *       See api.class.php::updateItems() -> To update a single item by item type and ID.
 *
 * TODO 1: How to import data?
 *       Could also extend class APIRest.class.php (or use it as an example), but seems
 *       unnecessary.
 *
 *       Also, see plugins/genericobject/inc/object.class.php->addOrUpdateObject() for an
 *       example using plugin DataInjection instead of GLPI's API.
 *
 * TODO 2: How to get duplication notifications and other undetected errors
 *    1- See: glpi/htdocs/inc/commondbtm.class.php::checkUnicity [3993, 4030, 4093, 4101, 4114]
 *       - FieldUnicity::getUnicityFieldsConfig
 *          >> $fields['action_notify'] + NotificationEvent::raiseEvent(
 *                'refuse',
 *                new FieldUnicity(), <== ****** Responsable de gérer l'évenement. Voir comment.
 *                $params
 *          )
 *       Conclusions: Notification par courriel. Pas d'exception.
 *       
 *    2- See: glpi/htdocs/inc/commondbtm.class.php::checkUnicity [3993, 4030, 4101]
 *       - FieldUnicity::getUnicityFieldsConfig
 *          >> $fields['action_refuse']
 *       Comment paramétrer ceci? $fields['action_refuse'] = true
 *       
 */
class ImporterByApi implements ImporterOfItemTypeInterface
{
   protected $api;
   
   /**
    * Constructor
    *
    * Debug: Class relies on a brittle hack to bypass app_token validation.
    *
    * @param   string   $url_base_api  (Optional) The base URL for the API
    *                                  (Mostly for building documentation URLs)
    *
    */
   function __construct($url_base_api = NULL) {
      $this->api = new \PluginETLAPI($url_base_api, $url_base_api);
   }

   /**
    * Import a single instance of item type into GLPI.
    *
    * Expectations:
    *    - A single database table per item type.
    *    - There exists a data model for item type and it is already configured to autoload. 
    *
    * @param   string         $item_type   The item type name as per usual GLPI conventions.
    * @param   object/array   $data        An array/object that matches the structure of the item type.
    *
    * @return array  A list of error (empty when no error)
    */
   public function importRecord(
      string $item_type,
      $data
   ) {
      
      if(is_array($data)) {
         $data = (object) $data;
      }
      
      if( ! is_object($data)) {
         throw new \Exception('Usage: Parameter $data must be an object or an associative array.');
      }
      
      // Convert PHP errors into exceptions and report problem.
      //
      // Use case: Stop script execution on PHP error. GLPI error handler logs errors without
      //           stopping scripts. This doesn't appear prudent when saving data, it could
      //           lead to invalid states in the database.
      //
      // For example: Trying to import a FieldUnicity with "_fields" instead of "fields" will
      //    result in a PHP warning about an invalid argument for function "implode",
      //    detected before the actual DB transaction in CommonDbtm::prepareInputForAdd,
      //    a method meant to be overwritten by model classes to preprocess inputs
      //    before adding them to the database.
      //    - Expectation: Don't try saving to the database when a PHP error occurs.
      //    - Result with GLPI error handler: Despite the PHP error occuring before the
      //                                      DB transaction, save to DB with invalid data.
      //
      // Risk: Stopping after saving into the DB might prevent some post processing
      //       and still leave the data in a dirty state. However, we expect the data to be
      //       valid before it's saved into the DB. So, how critical is post processing to
      //       data correctness? But can we still trust post processing after a PHP error?
      //
      // Note: Setting an error reporter that simply returned false did not stop
      //       script execution. Throwing an exception worked as expected.
      set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline){
         throw new \Exception(sprintf(
            'PHP SERVER ERROR'.PHP_EOL.'Level %s >> %s'.PHP_EOL.'%s(%s)'.PHP_EOL,
            $errno,
            $errstr,
            $errfile,
            $errline
         ));
         return false;
      });
      
      //Prevent output to the HTTP client. This is a backend library.
      ob_start();
      $result = '';
      
      try {
         if(isset($data->id)) {
            //\Toolbox::logDebug('Attempting to update an item for item type: '.$item_type);
            $this->api->updateItem($item_type, $data);
         } else {
            //\Toolbox::logDebug('Attempting to create an item for item type: '.$item_type);
            $this->api->createItem($item_type, $data);
         }
      } catch(RecoverableException $e) {
         // Errors should have already been recorded into $this->api->errors prior to emitting
         // this kind of exception. This exception is just for controlling execution flow, 
         // because the API class extends a GLPI class that may respond directly to the HTTP client.
         // That class was modified to emit this exception instead of outputting to the client.
      } catch(\Exception $e) {
         // Should mostly be PHP errors converted to exceptions by local error handler.
         $this->api->addError(
            \PluginETLAPI::createErrorObject(
               $e,
               500,
               'ERROR',
               ''
            )
         );
      } finally {
         $result = ob_get_clean();
      }
      
      // Resume usage of GLPI' error handler.
      restore_error_handler();
      
      if( ! empty($result)) {
         \Toolbox::logDebug('TODO: Update PluginETLAPI class to prevent direct output to the HTTP client.');
         \Toolbox::logDebug('Outputted result: '.PHP_EOL.var_export($result, true));
         $this->api->addError(
            \PluginETLAPI::createErrorObject(
               $result,
               500,
               'ERROR'
            )
         );
      }
      
      return $this->api->getErrors();
   }

}