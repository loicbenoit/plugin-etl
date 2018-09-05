<?php
use \PluginETL\errors\Error as Error;
use \PluginETL\errors\RecoverableException as RecoverableException;
use \PluginETL\errors\ErrorManagementTrait as ErrorManagementTrait;
use \PluginETL\helpers\ObjectHelper as ObjectHelper;

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
 * Handles programmatic acces to the GLPI API using the currently active session.
 *
 * Note: This class extend the GLPI API class in order to serve as a library for extracting
 *       and loading itemtypes into GLPI while circumventing some bugs and limitations of
 *       plugin DataInjection.
 *
 * Alternative strategies for importing data.
 *    Could also extend class APIRest.class.php (or use it as an example), but seems
 *    unnecessary.
 *
 *    Also, see plugins/genericobject/inc/object.class.php->addOrUpdateObject() for an
 *    example using plugin DataInjection instead of GLPI's API. But same plugin limitations.
 *
 * How to get duplication notifications and other errors not reported to the code?
 *    $_SESSION["MESSAGE_AFTER_REDIRECT"] should contain all other notifications/error msg.
 *
 *    On the subject of field unicity:
 *    glpi/htdocs/inc/commondbtm.class.php::checkUnicity [3993, 4030, 4093, 4101, 4114]
 */
class PluginETLAPI extends API
{
   use ErrorManagementTrait;
   
   // Base URL for all documentation.
   // Static only by accident, based on variable $api_url in parent API class.
   protected static $doc_base_url = '';
   
   /**
    * Constructor
    *
    * @param   string   $url_base_api  (Optional) The base URL for the API
    * @param   string   $doc_base_url  (Optional) The base URL for the documentation
    *                                  Default: Same as url_base_api for historical reasons.
    */
   function __construct($url_base_api = NULL, $doc_base_url = NULL) {
      //Based on parent class.
      if( ! is_string($url_base_api)) {
         global $CFG_GLPI;
         $url_base_api = $CFG_GLPI['url_base_api'];
      }
      
      //Because GLPI global may not be good.
      $url_base_api = is_string($url_base_api) ? $url_base_api : '';
      self::$api_url = trim($url_base_api, '/');
      
      self::$doc_base_url = is_string($doc_base_url) && strlen($doc_base_url) > 0
         ? $doc_base_url
         : self::$api_url; //For backward compatiblity with parent class.
      
      // 2018-08-30: Removing hack, because stopped using methods createItems() and updateItems().
      //             Use this hack if you need those methods, but it's easy to break (brittle code).
      //             ! Keep this comment for documentation purposes !
      // A hack for this class to behave like a backend library without rewriting GLPI's API class.
      // Goal: Circumvent API token validation. Use current session instead.
      // See: inc/api.class.php->checkAppToken() near "if (!$this->apiclients_id = array_search..."
      //$this->apiclients_id = '1';
      //$this->parameters['app_token'] = $this->apiclients_id;
      //$this->app_tokens = [1 => $this->parameters['app_token']];
      
      // A hack to circumvent session_token validation in this class.
      // It's the caller of this class that should be handling authentication and permissions.
      //$this->parameters['session_token'] = session_id();
   }
   
   
   /**
    * Add an object to GLPI
    *
    * @param string  $itemtype   The itemtype (class) of the object to update.
    * @param array   $object     An object with the properties of the specified itemtype.
    *
    * @return int The id of the new item.
    * @throws RecoverableException   Use getErrors() to get error list.
    * @todo Validate assumption that all notifications are errors. If false, find a way
    *       to distinguish error notifications from non-error notifications.
    */
   public function createItem($item_type, $object) {
      global $_SESSION;
      
      $model = $this->getModel($item_type);
      
      //For backward compatibility with GLPI methods that expect arrays instead of objects.
      $item = ObjectHelper::recursivelySerializeObjectToArray($object);
      
      //-----------------------------------------
      // Check permissions
      //-----------------------------------------
      if ( ! $model->can(-1, CREATE, $item)) {
         $this->addErrorForItem(
            __("You don't have permission to create this"),
            $item_type,
            '',
            403,
            'ERROR_METHOD_NOT_ALLOWED'
         );
         throw new RecoverableException();
      }
      
      //-----------------------------------------
      // Prepare to create
      //-----------------------------------------
      //Automatically choose current entity if missing.
      if ( ! isset($item['entities_id'])) {
         $item['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      
      $item = Toolbox::sanitize($item);

      //-----------------------------------------
      // Create
      //-----------------------------------------
      $new_id = $model->add($item);

      //-----------------------------------------
      // Report
      //-----------------------------------------
      if($new_id === false) {
         $this->addErrorForItem(
            __("Failed to create"),
            $item_type,
            '',
            400,
            'ERROR_GLPI_ADD'
         );
      }

      $this->collectSQLErrors();
      $this->collectNotificationErrors();
      $this->clearGLPINotifications();
      
      if($this->hasError()) {
         throw new RecoverableException();
      }
      
      return $new_id;
   }
   
   
   /**
    * Update an object by item type.
    *
    * @param string           $itemtype   The itemtype (class) of the object to update.
    * @param object/StdClass  $object     An object with the properties of the specified itemtype.
    *
    * @return void
    */
   public function updateItem(string $item_type, $object) {

      if( ! isset($object->id)) {
         $this->addErrorForItem(
            __("Missing property object->id. Usage: Provide the ID of the object to update."),
            $item_type,
            '?',
            400,
            'ERROR_GLPI_UPDATE'
         );
         throw new RecoverableException();
      }
      
      $model = $this->getModel($item_type);
      
      //For backward compatibility with GLPI methods that expect arrays instead of objects.
      $item = ObjectHelper::recursivelySerializeObjectToArray($object);
      
      if( ! isset($item['id'])) {
         throw new Exception('Bug: ObjectHelper::recursivelySerializeObjectToArray() removed property "id".');
      }
      
      //-----------------------------------------
      // Check permissions
      //-----------------------------------------
      if ( ! $model->can($item['id'], UPDATE)) {
         $this->addErrorForItem(
            __("You don't have permission to update this"),
            $item_type,
            $item['id'],
            403,
            'ERROR_METHOD_NOT_ALLOWED'
         );
         throw new RecoverableException();
      }
      
      //-----------------------------------------
      // Prepare to update
      //-----------------------------------------
      if( ! $model->getFromDB($item['id'])) {
         $this->addErrorForItem(
            __("Item not found"),
            $item_type,
            $item['id'],
            400,
            'ERROR_ITEM_NOT_FOUND'
         );
         throw new RecoverableException();
      }
      
      $item = Toolbox::sanitize($item);
      
      //-----------------------------------------
      // Update
      //-----------------------------------------
      $was_updated = $model->update($item);
      
      //-----------------------------------------
      // Report
      //-----------------------------------------
      if( ! $was_updated) {
         $this->addErrorForItem(
            __("Update failed for "),
            $item_type,
            $item['id'],
            400,
            'ERROR_GLPI_UPDATE'
         );
      }

      $this->collectSQLErrors();
      $this->collectNotificationErrors();
      $this->clearGLPINotifications();
   }


   /**
    * Get an instance of the given item type.
    * Item types are GLPI's data models.
    *
    * @return object  An object of class $item_type.
    * @throws RecoverableException when class $item_type is not found.
    */
   public function getModel(string $item_type) {
      if( ! class_exists($item_type)) {
         $this->addError(
            self::createErrorObject(
               'Classe introuvable: '.$item_type,
               400,
               'ERROR',
               ''
            )
         );
         
         throw new RecoverableException();
      }
      
      return new $item_type;
   }

 
   /**
    * {@inheritDoc} Collect errors in array $this->errors instead of outputing to the HTTP client.
    *
    * If $return_response is true, method returnResponse may throw an exception instead of
    * producing an HTTP response.
    *
    * @param string  $message         message to send (human readable)(default 'Bad Request')
    * @param integer $httpcode        http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    *                                      (default 400)
    * @param string  $statuscode      API status (to represent more precisely the current error)
    *                                      (default ERROR)
    * @param boolean $docmessage      if true, add a link to inline document in message
    *                                      (default true)
    * @param boolean $return_response Overwritten to always be true.
    *
    * @return array  A serialized Error object (see method createErrorArray()).
    */
   public function returnError($message = "Bad Request", $httpcode = 400, $statuscode = "ERROR",
                               $docmessage = true, $return_response = true) {
      if (empty($httpcode)) {
         $httpcode = 400;
      }
      if (empty($statuscode)) {
         $statuscode = "ERROR";
      }

      $error = self::createErrorArray(
         $message,
         $httpcode,
         $statuscode
      );
      
      // Because the parent GLPI class expects errors to be arrays, but this class prefers objects.
      $this->addError(self::convertErrorArrayToObject($error));

      // Because parent method allows this.
      if( ! $docmessage) {
         unset($error['doc_message']);
      }
      
      if($return_response) {
         throw new RecoverableException();
      }
      
      return $error;
   }

   
   /**
    * {@inheritDoc} Return the default base URL of the documentation.
    * Note: Mainly to hide away the use of a static variable for doc base URL.
    *
    * @return string
    */
   public static function getDefaultDocumentationBaseUrl() {
      return self::$doc_base_url;
   }

   
   /**
    * {@inheritDoc} Not needed, this is a library. No direct HTTP interactions.
    *
    * @return void self::returnResponse called for output
    */
   public function call() {
      throw new Exception('Method not implemented. This class is a backend library. No HTTP support.');
   }

   /**
    * {@inheritDoc} Not needed, this is a library. No direct HTTP interactions.
    *
    * @return string endpoint called
    */
   protected function parseIncomingParams() {
      throw new Exception('Method not implemented. This class is a backend library. No HTTP support.');
   }

   /**
    * {@inheritDoc} Not needed, this is a library. No direct HTTP interactions.
    *
    * @param mixed   $response          string message or array of data to send
    * @param integer $code              http code
    * @param array   $additionalheaders headers to send with http response (must be an array(key => value))
    *
    * @return void
    */
   protected function returnResponse($response, $code, $additionalheaders) {
      throw new Exception('Method not implemented. This class is a backend library. No HTTP support.');
   }

   /**
    * {@inheritDoc} Not needed, this is a library. No direct HTTP interactions.
    *
    * @return void
    */
   protected function manageUploadedFiles() {
      throw new Exception('Method not implemented. This class is a backend library. No HTTP support.');
   }   

}