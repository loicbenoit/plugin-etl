<?php
namespace PluginETL\helpers;

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
 * A few object related helper functions.
 * Note: Mostly here for licensing reasons, because code was adapted from GLPI.
 */
class ObjectHelper
{

   /**
    * Serialize an object into an array
    *
    * Adapted from: glpi/htdocs/inc/api.class.php::(private)inputObjectToArray
    *
    * @param  object    $object
    * @param  bool      $recurse    Apply this function recursively to the object's properties.
    *                               (Defaults to false)
    *
    * @return array
    */
   public static function serializeObjectToArray($object, $recurse = false) {
      if (is_object($object)) {
         $object = get_object_vars($object);
      }
      
      if($recurse && is_array($object)) {
         foreach ($object as $key => &$value) {
            $value = self::serializeObjectToArray($value, $recurse);
         }
         unset($value);
      }

      return $object;
   }

   /**
    * Serialize an object into an array, and recursively serialize the object's properties.
    *
    * @param  object    $object
    *
    * @return array
    */
   public static function recursivelySerializeObjectToArray($object) {
      return self::serializeObjectToArray($object, true);
   }
}