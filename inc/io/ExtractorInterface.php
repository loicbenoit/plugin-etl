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
 * Interface for extracting individual item types from GLPI.
 *
 */
Interface ExtractorInterface
{
   /**
    * Tell if the extractor knows how to extract data from a source, by name.
    *
    * @param   string   $source_name
    *
    * @return bool   TRUE if source_name is supported, else FALSE.
    */
   public function knowsSource(string $source_name);
   
   /**
    * Extract data from a source. 
    *
    * @param   object   $source  A source object (see RETL library)
    *
    * @return array  A list of data objects from source.
    */
   public function extract(string $source);

}