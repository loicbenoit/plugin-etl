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
 * Concrete class for extracting individual item types from GLPI.
 *       
 */
class ExtractorByApi implements ExtractorInterface
{
   protected $api;
   
   /**
    * Constructor
    *
    * @param   string   $url_base_api  (Optional) The base URL for the API
    *                                  (Mostly for building documentation URLs)
    *
    */
   function __construct($url_base_api = NULL) {
      $this->api = new \PluginETLAPI($url_base_api);
   }

   /**
    * Tell if the extractor knows how to extract data from a source, by name.
    *
    * @param   string   $source_name
    *
    * @return bool   TRUE if source_name is supported, else FALSE.
    * @todo Implement
    */
   public function knowsSource(string $source_name) {
      return false;
   }
   
   /**
    * Extract data from a source. 
    *
    * @param   object   $source  A source object (see RETL library)
    *
    * @return array  A list of data objects from source.
    * @todo Implement
    */
   public function extract(string $source) {
      return [];
   }

}