<?php
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

include ('../../../inc/includes.php');

if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
   Html::header("ETL", $_SERVER['PHP_SELF'], "plugins", "etl", "etl");
} else {
   Html::helpHeader("ETL", $_SERVER['PHP_SELF']);
}

// Names of input in form
$file_input_name = 'csvfile';
$plan_input_name = 'etlplan';
$csv_delimiter_input_name = 'csvdelimiter';

// Default values
$default_csv_delimiter = ',';

//Action routing
$action = 'showForm';

if(isset($_POST[$plan_input_name])
   && isset($_FILES[$file_input_name]['tmp_name'])
   && is_uploaded_file($_FILES[$file_input_name]['tmp_name'])) {
   $action = 'uploadCsv';
}

//-----------------------------------------------
//Control logic
//-----------------------------------------------
// Array $views is a list of view partials in echo order, with associated data.
// Each array item must be an array with the following syntax:
//    [
//       'uri' => '', //Relative file path below ".../inc/views/"
//       'data' => [], //Associative array of "variable name" to "value".
//    ]
//
// Expected: No echoing inside controller methods. The only way to display content with PHP is
// through a view. Else, use JS scripts if PHP views can't accomodate your requirements.
// Question: How to use GLPI standard methods for displaying content (they usually echo stuff)?
// Answer: Use them inside view files or wrap them in output buffers (see ob_start).
$views = [];
try {
   switch($action) {
      case 'uploadCsv' :
         $ctlr = new \PluginETL\ctrl\CsvImport(
            $plan_input_name,
            $file_input_name,
            $csv_delimiter_input_name,
            $default_csv_delimiter
         );
         
         $ctlr->importCsv(
            $ctlr->getPlanNameFromRequest(),
            $ctlr->getFilePathFromRequest(),
            $ctlr->getCsvDelimiterFromRequest()
         );
         
         //Prepare HTML partial for file upload form
         $views[] = [
            'uri' => '/csvimport/form.php',
            'data' => $ctlr->prepareFormViewData(),
         ]; 
         
         //Prepare HTML partial for job report
         $views[] = [
            'uri' => '/csvimport/report.php',
            'data' => $ctlr->prepareReportViewData(),
         ]; 
         break;
      
      case 'showForm' :
      default:
         $ctlr = new \PluginETL\ctrl\CsvImport(
            $plan_input_name,
            $file_input_name,
            $csv_delimiter_input_name,
            $default_csv_delimiter
         );
         
         //Prepare HTML partial for file upload form
         $views[] = [
            'uri' => '/csvimport/form.php',
            'data' => $ctlr->prepareFormViewData(),
         ];
         break;
   }
} catch(\PluginETL\errors\FrontendFeedbackException $e) {
   $view = [
      'uri' => '/csvimport/error.php',
      'data' => [
         errors => [$e->getMessage()],
      ],
   ];
   //Setup errors to display near the top (unless otherwise specified by CSS).
   array_unshift($views, $view);
   
} finally {
   //Display page content
   foreach($views as $view) {
      echo PluginETLView::load($view['uri'], $view['data']);
   }   
}

Html::footer();
