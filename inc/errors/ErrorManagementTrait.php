<?php
namespace PluginETL\errors;

use \PluginETL\errors\Error as Error;

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
 * Trait for standardized error management and reporting.
 * Not an ideal solution for error management, but good enough for a single plugin.
 * 
 * Warning: Some methods rely on GLPI global variables.
 * Contains an abstract method: getDefaultDocumentationBaseUrl()
 */

Trait ErrorManagementTrait
{
   protected $errors = [];
   

   /**
    * Utility method to add an error related to an object with an item type.
    * Reminder: item type == model class
    *
    * Uses methods generateErrorMsgForItem(), createErrorObject() and addError().
    *
    * @param   string   $prefix        The message to display before the string identifing the object. 
    * @param   string   $item_type     The model class name.
    * @param   mixed    $id            The object ID (usually the object's primary key in the database)
    * @param   string   $http_code     An HTTP status code
    * @param   string   $status_code   API status (to represent more precisely the current error)
    * 
    * @return void
    */
   public function addErrorForItem(
      string $prefix = '',
      string $item_type = '?',
      $id = '?',
      $http_code = 400,
      $status_code = 'ERROR'
   ) {
      $this->addError(
         self::createErrorObject(
            self::generateErrorMsgForItem(
               $prefix,
               $item_type,
               $id
            ),
            $http_code,
            $status_code
         )
      );
   }

   /**
    * Generate an error message that includes a description of an object.
    *
    * All inputs are optional. The generated string may report missing information with '?'.
    *
    * @param   string   $prefix     The message to display before the string identifing the object. 
    * @param   string   $item_type  The model class name.
    * @param   mixed    $id         The object ID (usually the object's primary key in the database)
    * @param   string   $suffix     The message to display after the string identifing the object.
    * 
    * @return string The formatted message.
    */
   public static function generateErrorMsgForItem(
      string $prefix = '',
      string $item_type = '?',
      $id = '?',
      $suffix = ''
   ) {
      $prefix = strlen($prefix) > 0 ? $prefix.' >>> ' : '';
      $item_type = strlen($item_type) > 0 ? $item_type : '?';
      $id = (string) $id;
      $suffix = strlen($suffix) > 0 ? '. '.$suffix : '';
      
      if(strlen($id) > 0) {
         return sprintf(
            '%s%s::id = %s%s',
            $prefix,
            $item_type,
            $id,
            $suffix
         );
      } else {
         return sprintf(
            '%s%s%s',
            $prefix,
            $item_type,
            $suffix
         );
      }
   }

   
   /**
    * Return the list of SQL errors as reported by GLPI.
    *
    * @return array  A list of Error objects or an empty list.
    * @todo Find the data format(s) to expect from $DEBUG_SQL['errors']
    * @todo Imporve conversion of GLPI SQL errors into Error objects
    */
   public function getSQLErrors() {
      global $DEBUG_SQL;
      $retval = [];
      
      //Normalize global variable $DEBUG_SQL['errors']
      $errors = is_array($DEBUG_SQL) && isset($DEBUG_SQL['errors']) ? $DEBUG_SQL['errors'] : [];
      $errors = is_array($errors) ? $errors : [$errors];
      
      //For debugging purposes only.
      if(count($errors) > 0) {
         Toolbox::logDebug('SQL errors: '.PHP_EOL.var_export($errors, true));
      }
      
      //Convert each error into an error object
      foreach($errors as $error) {
         $retval[] = self::createErrorObject(var_export($error, true));
      }
      
      return $retval;
   }

   
   /**
    * Get GLPI notifications, if any as Error objects.
    * Use case: Unicity check notifications
    *
    * @return array A list of Error objects
    */
   protected function getNotificationErrors() {
      //Convert each error into an error object
      //TODO: Should we call Html::clean()? i.e.: Do getNotifications(true) instead?
      
      $retval = [];
      
      foreach($this->getNotifications(false) as $msg) {
          $error = self::createErrorObject($msg);
          //TODO: Validate that it's ok not to escape the HTML produced by GLPI.
          $error->escape_callback = function($value) { return $value; };
          $retval[] = $error;
      }
      
      return $retval;
   }

   
   /**
    * Get GLPI notifications, if any.
    * Use case: Unicity check notifications
    *
    * @param   bool  $escape  (Optional) TRUE to escape each message with Html::clean()
    *
    * @return array A list of strings
    * @todo This should be in a class that manages notifications.
    */
   protected function getNotifications($escape = true) {
      $retval = [];
      
      $messages_after_redirect = (
            isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
            && count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0
         )
         ? $_SESSION["MESSAGE_AFTER_REDIRECT"]
         : [];

      foreach($messages_after_redirect as $type => $messages) {
         foreach($messages as $message) {
            $retval[] = $escape ? Html::clean($message) : $message;
         }
      }
      
      return $retval;
   }

   
   /**
    * Remove all notifications messages from session.
    *
    * @return void
    * @todo This should be in a class that manages notifications.
    */
   protected function clearGLPINotifications() {
      $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
   }

   
   /**
    * Add one error per SQL error, if any.
    * Uses method "addError".
    * 
    * @return void
    */
   protected function collectSQLErrors() {
      $sql_errors = $this->getSQLErrors();
      if(count($sql_errors) > 0) {
         $this->addErrors($sql_errors);
      }
   }
   
   /**
    * Add one error per GLPI notification, if any.
    * Uses method "addError".
    *
    * Use case:   The number of notifications may be too high for a popup notification box...
    *             Instead, display each notification on page by adding one error per notification.
    *
    * Recommandation:   Call method "clearGLPINotifications" just after this method, to prevent the
    *                   popup notification box from showing.
    * 
    * @return void
    */
   protected function collectNotificationErrors() {
      $notifications = $this->getNotificationErrors();
      if(count($notifications) > 0) {
         foreach($notifications as $error) {
            $this->addError($error);
         }
      }
   }
   
   /**
    * Return the list of recorded errors
    *
    * @return array  A list of Error objects or an empty list.
    */
   public function getErrors() {
      return $this->errors;
   }
   
   /**
    * Tell if at least one error was recorded.
    *
    * @return bool
    */
   public function hasError() {
      return count($this->errors) > 0;
   }
   
   /**
    * Remove all error
    *
    * @return void
    */
   public function clearErrors() {
      $this->errors = [];
   }
   
   /**
    * Append an error to the list of errors.
    *
    * @param   Error $error
    *
    * @return void
    */
   public function addError(Error $error) {
      $this->errors[] = $error;
   }
   
   /**
    * Append a list of errors to the list of errors.
    *
    * @param   array    $errors  A list of 
    *
    * @return void
    */
   public function addErrors(array $errors) {
      foreach($errors as $error) {
         $this->addError($error);
      }
   }

   
   /**
    * Create a new standardised error object
    *
    * @param string  $message       Human readable message(default 'Bad Request')
    * @param numeric $http_code     http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    *                               (default 400)
    * @param string  $status_code   API status (to represent more precisely the current error)
    *                               (default ERROR)
    * @param string  $doc_message   (Optional) Link to inline document in message
    *                               (default: generateDocumentationCitation() with default template)
    *                               
    *
    * @return array
    */
   public static function createErrorObject(
      string $message = "Bad Request",
      $http_code = 400,
      string $status_code = "ERROR",
      $doc_message = NULL
   ) {
      $doc_message = is_string($doc_message)
         ? $doc_message
         : self::generateDocumentationCitation(
            self::getDocumentationUrlForStatusCode($status_code),
            self::getDefaultDocumentationCitationTemplate()
      );
      
      return new Error(
         $message,
         $http_code,
         $status_code,
         $doc_message
      );
   }
   
   /**
    * Create a new standardised error object
    *
    * @param array   $error  An array created with method createErrorArray
    *
    * @return object An instance of Error
    */
   public static function convertErrorArrayToObject(Array $error) {
      return self::createErrorObject(
         $error['message'],
         $error['status_code'],
         $error['http_code'],
         $error['doc_message']
      );
   }
   
   /**
    * Create a new standardised error array
    *
    * @param string  $message       Human readable message(default 'Bad Request')
    * @param integer $http_code     http code (see : https://en.wikipedia.org/wiki/List_of_HTTP_status_codes)
    *                               (default 400)
    * @param string  $status_code   API status (to represent more precisely the current error)
    *                               (default ERROR)
    * @param boolean $doc_message   (Optional) Link to inline document in message
    *                               (default: generateDocumentationCitation() with default template)
    *                               
    *
    * @return array
    */
   public static function createErrorArray(
      $message = "Bad Request",
      $http_code = 400,
      $status_code = "ERROR",
      $doc_message = NULL
   ) {
      $doc_message = is_string($doc_message) && strlen($doc_message)
         ? $doc_message
         : self::generateDocumentationCitation(
            self::getDocumentationUrlForStatusCode($status_code),
            self::getDefaultDocumentationCitationTemplate()
      );
      
      return [
         'message' => $message,
         'status_code' => $status_code,
         'http_code' => $http_code,
         'doc_message' => $doc_message,
      ];
   }
   
   /**
    * Return a string explaining where to find some documentation.
    *
    * @param string  $url        URL of the cited documentation
    * @param string  $template   A sprintf format string (must have already been translated).
    *
    * @return string
    */
   public static function generateDocumentationCitation(
      string $url,
      string $template
   ) {
      return sprintf($template, $url);
   }
   
   /**
    * Return the URL of the documentation related to a given status code.
    *
    * @param string  $status_code   API status (see method createErrorArray)
    * @param string  $api_url       URL to the API (default: self::getDefaultDocumentationBaseUrl())
    *
    * @return string    The URL or an empty string.
    */
   public static function getDocumentationUrlForStatusCode(
      $status_code,
      string $url = NULL
   ) {
      // Correction: The documentation uses "#errors", not '#error'.
      if(strtolower($status_code) == "error") {
         $status_code .= 's';
      }
      
      // Correction: The documentation uses lower cased anchors.
      // It shouldn't matter in HTML5, but browsers, like Chrome, often still apply
      // the historical case sensitive convention.
      $status_code = strtolower($status_code);
      
      // Actual method logic
      $url = is_string($url) && strlen($url) > 0 ? $url : self::getDefaultDocumentationBaseUrl();
      return ! empty($url) ? $url.'/#'.$status_code : '';
   }
   
   /**
    * Return the default base URL of the error documentation.
    *
    * @return string
    */
   abstract public static function getDefaultDocumentationBaseUrl();
   
   /**
    * Return the default string explaining where to find some documentation.
    *
    * Will translate the string into the current request language.
    * Should return a sprintf format string that accepts a single string parameter (%s).
    *
    * @return string
    */
   public static function getDefaultDocumentationCitationTemplate() {
      return __("view documentation in your browser at %s");
   }

}