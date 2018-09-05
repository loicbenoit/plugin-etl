<?php
namespace PluginETL\io;

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
 * For importing individual item types into GLPI.
 *
 */
Interface ImporterOfItemTypeInterface
{
   /**
    * Import a single instance of item type into GLPI.
    *
    * Expectations:
    *    - A single database table per item type.
    *    - There exists a class to model the item type and it is already configured to autoload. 
    *
    * @param   string         $item_type   The item type name as per usual GLPI conventions.
    * @param   object/array   $data        An array/object that contains the property-value pairs
    *                                      of the item type.
    *
    * @return array  A list of error (empty when no error)
    */
   public function importRecord(
      string $item_type,
      $data
   );

}