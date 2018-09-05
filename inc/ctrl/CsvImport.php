<?php
namespace PluginETL\ctrl;

use \League\Csv\Reader as Reader;
use \League\Csv\CharsetConverter as CharsetConverter;
use \retl\helpers\Names;
use \PluginETL\io\ExtractorByApi;
use \PluginETL\io\ImporterByApi;

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
 * Controller logic for importing CSV files using RETL library.
 */
class CsvImport
{
   // Names of various form inputs.
   protected $plan_input_name = 'etlplan';
   protected $file_input_name = 'csvfile';
   protected $csv_delimiter_input_name = 'csvdelimiter';
   
   // Associative array of variable to value for the form view.
   protected $form_data = [];

   // Associative array of variable to value for the report view.
   protected $report_data = [];
   
   protected $importer;
   protected $transformer;
   protected $extractor;
   
   const FETCH_SOURCE_DATA_METHOD_PREFIX = 'fetchSourceData';
   
   /**
    * Constructor
    *
    * @param   string   $plan_input_name        Name of the form input that will receive the plan name.
    * @param   string   $file_input_name        Name of the form input that will receive the CSV file.
    * @param   string   $csv_delimiter_input_name   Name of the form input that will receive the CSV delimiter.
    * @param   string   $default_csv_delimiter   (Optional) default CSV delimiter (defaults to comma).
    *
    */
   function __construct(
      string $plan_input_name = NULL,
      string $file_input_name = NULL,
      string $csv_delimiter_input_name = NULL,
      string $default_csv_delimiter = ','
   ) {
      if(is_string($plan_input_name) && strlen($plan_input_name) > 0) {
         $this->plan_input_name = $plan_input_name;
      }
      if(is_string($file_input_name) && strlen($file_input_name) > 0) {
         $this->file_input_name = $file_input_name;
      }
      if(is_string($csv_delimiter_input_name) && strlen($csv_delimiter_input_name) > 0) {
         $this->csv_delimiter_input_name = $csv_delimiter_input_name;
      }
      if(is_string($default_csv_delimiter) && strlen($default_csv_delimiter) > 0) {
         $this->default_csv_delimiter = $default_csv_delimiter;
      }
      
      $this->extractor = new ExtractorByApi();
      //$this->tranformer = new RETL...
      $this->importer = new ImporterByApi();
   }

   /**
    * Prepare the list of variables to pass to the form view.
    * Array format:
    *    - Key: The name of the variable in the view script.
    *    - Value: The value to assign to the view variable.
    *
    * Will try to use existing values from POST, else will use default values so that the
    * page can reload without clearing the form.
    *
    * @return array   The content of form_data
    */
   public function prepareFormViewData() {
      $this->form_data = [
         'chosen_plan' => $this->getPlanNameFromRequest(), 
         'plan_input_name' => $this->getPlanInputName(),
         'file_input_name' => $this->getFileInputName(),
         'csv_delimiter_input_name' => $this->getCsvDelimiterInputName(),
         'csv_delimiter' => $this->getCsvDelimiterFromrequest(),
      ];
      
      return $this->getFormViewData();
   }

   /**
    * Return the list of variables to pass to the form view.
    * Array format:
    *    - Key: The name of the variable in the view script.
    *    - Value: The value to assign to the view variable.
    *
    * @return array   The content of form_data
    */
   public function getFormViewData() {
      return $this->form_data;
   }

   /**
    * Prepare the list of variables to pass to the report view.
    * Array format:
    *    - Key: The name of the variable in the view script.
    *    - Value: The value to assign to the view variable.
    *
    * @return array   The content of report_data
    */
   public function prepareReportViewData() {
      $this->report_data['original_file_name'] = $this->getClientFileNameFromRequest();
      
      if(empty($this->report_data['errors'])) {
         $this->report_data['success_msg'] = __("Vos données ont été importées correctement.");
      }
      return $this->getReportViewData();
   }

   /**
    * Return the list of variables to pass to the report view.
    * Array format:
    *    - Key: The name of the variable in the view script.
    *    - Value: The value to assign to the view variable.
    *
    * @return array   The content of report_data
    */
   public function getReportViewData() {
      return $this->report_data;
   }

   /**
    * Import a CSV file using provided plan name and file path.
    * Note: The CSV file must have a header (all column must be named).
    *
    * @param   string   $plan          A plan file path relative to the plans directory.
    * @param   string   $file_path     An absolute file path to a CSV file.
    * @param   string   $csv_delimiter (Optional) CSV field delimiter (defaults to a comma: ',')
    * 
    * @return void
    */
   public function importCsv(string $plan, string $file_path, string $csv_delimiter = ',') {
      //-----------------------------------------
      // Fetch data
      //-----------------------------------------
      //TODO
      //$plan = $this->fetchPlan($plan);
      
      $csv = Reader::createFromPath($file_path);
      $csv->setHeaderOffset(0);
      $csv->setDelimiter($csv_delimiter);
      
      //-----------------------------------------
      // Prepare data
      // Note: Mock transformation, until RETL library is ready.
      //-----------------------------------------
      $output_records = [];
      foreach($csv as $record) {
         //TODO: How to get the alias of each output record?
         $output_records[] = $this->mockTransformRecord($plan, $record);
      }
      
      //\Toolbox::logDebug('Output records: '.PHP_EOL.var_export($output_records, true));
      
      //-----------------------------------------
      // Import data
      //-----------------------------------------
      $this->report_data['errors'] = [];
      foreach($output_records as $line_number => $record) {
         $errors = $this->importer->importRecord(
            $plan,            //Item type
            $record->$plan    //Data to save for the item type
         );
         
         if( ! empty($errors)) {
            $this->report_data['errors'][$line_number] = $errors;
         }
      }
   }


   /**
    * Import a CSV file using provided plan name and file path.
    *
    * @param   string   $plan       A plan file path relative to the plans directory.
    * @param   string   $file_path  An absolute file path to a CSV file.
    * 
    * @return void
    * @todo Error management, job management and reporting logic
    */
   public function importCsvWithRETL(string $plan, string $file_path) {
      //$plan = $this->fetchPlan($plan);
      //$input_records = $this->extract($plan, $file_path);
      //$output_records = $this->transform($plan, $input_records);
      //$this->load($plan, $output_records);
   }
   
   /**
    * Extract data from a CSV file and return a list of input records ready for RETL transformation.
    *
    * See library RETL for full documentation.
    *
    * In summary, this method must produce input records that conform to the input
    * specifications of the RETL plan. For each input record, every data source defined in the plan
    * must be populated. The RETL transformation will validate each input record. You may use
    * these methods to pre-validate your input or just catch errors while transforming:
    *    \RETL\Plan::__construct($plan_uri)
    *    +
    *    \RETL\Plan::prepare($record) => $record
    *    +
    *    \RETL\Plan::getErrors($record) => $errors
    *    +
    *    \RETL\Plan::hasError($record) => bool
    *
    * Plan vs Record vs PlanFile vs Job
    * 
    * //using a reversible semantic pipeline.
    * //Input record valiation is performed by the preparation phase of the
    * //RETL plan. The preparation phase must succeed before the transform phase can begin.
    * //Method "this->transform" executes the RETL transformation logic: prepare, transform, adjust.
    *
    * @param   object   $plan       An ETLPlan object (see RETL library).
    * @param   string   $file_path  An absolute file path to a CSV file.
    * 
    * @return array  A list of input records that conform to RETL library expectations
    * @toto Add a fetch_source_data_method for each secondary data source or create external classes.
    * @todo Use type "ETLPlan" instead of "object" in method signature, when RETL is ready.
    */
   protected function extract(object $plan, string $file_path) {
      $csv = Reader::createFromPath($file_path);
      $csv->setHeaderOffset(0);
      
      // Note: The CSV file is not the only data source. Other data sources might be
      //       values for external keys (a.k.a. dropdowns) or associated objects (a.k.a.
      //       object tabs in the GLPI GUI).
      //
      // Micro-optimisation: Use the locality of this for loop to cache dropdown data, as
      // dropdown definitions should be the same for all CSV records. It would be a wasteful
      // for repeat DB requests for such things.
      //
      // 1- Create a property for each data source.
      // 2- Populate each data source with data.
      //
      $input_records = [];
      foreach($csv as $line) {
         $record = new \StdClass();
         
         // Add primary data source(s) (data already present in raw input).
         if( ! $plan->hasSource('csv')) {
            throw new \Exception('Usage: Plan must contain a CSV source.');
         }
         $record->csv = $line;
         
         // Add secondary data source(s) (data not already contained in raw input)
         // For example: dropdown definitions, associated objects, etc.
         $record= $this->addDataSourcesToRecord(
            $record,
            $plan->getSourcesExcept(['csv'])
         );
         
         $input_records[] = $record;
      }
      return $input_records;
   }

   /**
    * Add data to an input record given a list of source data.
    *
    * Data from each source is assigned under a property named after the source.
    *
    * @param   object   $record
    * @param   array    $sources  A list of source objects.
    * 
    * @return  object   the modified record
    * @TODO Test
    */
   protected function addDataSourcesToRecord(object $record, array $sources) {
      foreach($sources as $source) {
         if( ! is_object($source) OR ! isset($source->name)) {
            throw new \Exception('Usage: A source must be an object and have a property called "name".');
            \Toolbox::logDebug('Invalid source. Must be object with property "name": '.PHP_EOL.var_export($source, true));
         }
         
         if( ! $this->extractor->knowsSource($source->name)) {
            throw new \Exception(
               sprintf(
                  'Extractor class "%s" does NOT know how to extract data source "%s".',
                  get_class($this->extractor),
                  $source->name
               )
            );
         }
         
         //TODO: If extract() returns an array, how to use obj accessors like $record->source_alias->property
         //      More precisely, how to use obj accesors on one to many associations? See jQeury syntax.
         $record->$source_name = $this->extractor->extract($source);
      }
      
      return $record;
   }
   
   
   /**
    * Convert an input record into an output record.
    *
    * The input/output records direct properties are data sources/destinations.
    * Properties of sources/destinations are actual key-value pairs to import.
    *
    * @param   Plan     $plan          A plan file path relative to the plans directory.
    * @param   array    $csv_record    A CSV line as associative array.
    * 
    * @return object
    * @todo Use library RETL to transform using a plan.
    */
   protected function transform(object $plan, array $input_records) {
      throw new \Exception('Not yet implemented.');
      $output_records = [];
      //... Use RETL
      return $output_records;
   }
   
   /**
    * Upload an output record into GLPI.
    *
    * The input/output records direct properties are data destinations (a.k.a. GLPI item_types).
    * Properties of sources/destinations are actual key-value pairs to import.
    *
    * @param   Plan     $plan      A plan file path relative to the plans directory.
    * @param   array    $record    A record to load.
    * 
    * @return void
    * @todo Implement, requires RETL.
    */
   protected function load(object $plan, array $output_records) {
      throw new \Exception('Not yet implemented.');
      foreach($output_records as $record) {
         //TODO: Use an importer class to decouple GLPI logic from this class.
         //TODO: Manage errors.
      }
   }

   /**
    * Convert an input record into an output record.
    *
    * The input/output records direct properties are data sources/destinations.
    * Properties of sources/destinations are actual key-value pairs to import.
    *
    * @param   string   $plan_name     A plan file path relative to the plans directory.
    * @param   array    $csv_record    A CSV line as associative array.
    * 
    * @return object
    * @todo Use library RETL to transform using a plan.
    */
   protected function mockTransformRecord(string $plan_name, array $csv_record) {
      //@hack: Using $plan_name as destination alias.
      return (object) [
         $plan_name => (object) $csv_record,
      ];
   }
      
   /**
    * Get the plan name from the current HTTP request.
    *
    * @return string The plan name as a relative path under the plans directory or an empty string.
    */
   public function getPlanNameFromRequest() {
      return isset($_POST[$this->getPlanInputName()])
         ? $_POST[$this->getPlanInputName()]
         : '';
   }

   /**
    * Get the absolute path of the file to import from the current HTTP request.
    *
    * @return string An absolute file path
    */
   public function getFilePathFromRequest() {
      return $_FILES[$this->getFileInputName()]['tmp_name'];
   }

   /**
    * Get the file provided by the client from the current HTTP request.
    *
    * Security: You MUST escape this value before using it.
    *
    * @return string The client file name or an empty string
    */
   public function getClientFileNameFromRequest() {
      return $_FILES[$this->getFileInputName()]['name'];
   }

   /**
    * Get the CSV delimiter provided by the client from the current HTTP request.
    * 
    * Security: You MUST escape this value before using it.
    *
    * @return string The CSv delimiter or an empty string
    */
   public function getCsvDelimiterFromRequest() {
      return isset($_POST[$this->getCsvDelimiterInputName()])
         ? $_POST[$this->getCsvDelimiterInputName()]
         : $this->default_csv_delimiter;
   }

   /**
    * Get the name of input field for selecting a file.
    *
    * @return string
    */
   public function getFileInputName() {
      return $this->file_input_name;
   }

   /**
    * Get the name of input field for selecting a plan.
    *
    * @return string
    */
   public function getPlanInputName() {
      return $this->plan_input_name;
   }

   /**
    * Get the name of input field for setting the Csv delimiter.
    *
    * @return string
    */
   public function getCsvDelimiterInputName() {
      return $this->csv_delimiter_input_name;
   }
   
   /**
    * Fetch a plan object given a plan name
    *
    * @param   string   $plan    A plan name or relative path
    * 
    * @return string
    * @todo Implement, requires RETL
    */
   protected function fetchPlan(string $plan) {
      throw new \Exception('Not yet implemented.');
      $retval = NULL;
      return $retval;
   }

}