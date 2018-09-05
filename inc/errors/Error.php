<?php
namespace PluginETL\errors;

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
 * Represents a single error
 *
 */
Class Error
{
   protected $message;
   protected $http_code;
   protected $status_code;
   protected $doc_message;
   public $escape_callback = NULL;
   
   public function __construct(
      $message = "Bad Request",
      $http_code = 400,
      $status_code = "ERROR",
      $doc_message = ''
   ) {
      $this->message = is_string($message) ? $message : "Bad Request";
      $this->http_code = is_numeric($http_code) ? $http_code : 400;
      $this->status_code = is_string($status_code) ? $status_code : "ERROR";
      $this->doc_message = is_string($doc_message) ? $doc_message : '';
      $this->escape_callback = function($value) { return htmlentities($value); };
   }
   
   /**
    * Accessor
    *
    * @return  string
    */
   public function getMessage() {
      return $this->message;
   }
   
   /**
    * Accessor
    *
    * @return  string
    */
   public function getHttpCode() {
      return $this->http_code;
   }
   
   /**
    * Accessor
    *
    * @return  string
    */
   public function getStatusCode() {
      return $this->status_code;
   }
   
   /**
    * Accessor
    *
    * @return  string
    */
   public function getDocMessage() {
      return $this->doc_message;
   }
   
   /**
    * Magic method that returns a string representation of this object when treated like a string.
    * See: http://php.net/manual/en/language.oop5.magic.php#object.tostring
    *
    * @return  string
    */
   public function __toString() {
      return sprintf(
         '%1$s'.PHP_EOL.'(%2$s - %3$s) %4$s',
         //'(%2$s - %3$s) %1$s'.PHP_EOL.'%4$s',
         $this->message,
         $this->http_code,
         $this->status_code,
         $this->doc_message
      );
   }
   
   /**
    * Returns an HTML representation of this object.
    *
    * @return  string
    */
   public function toHtml() {
      
      return sprintf(
         '<table class="plugin-etl__error-msg"><tbody><tr><td>%2$s - %3$s<br/>%4$s</td><td>%1$s</td></tr></tbody></table>',
         nl2br(
            call_user_func(
               $this->escape_callback,
               $this->message
            )
         ),
         htmlentities($this->http_code),
         htmlentities($this->status_code),
         htmlentities($this->doc_message)
      );
   }

}