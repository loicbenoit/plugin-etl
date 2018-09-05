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

   $mandatory_variables = [
      'plan_input_name' => "the name of the input field that will receive the plan name. Will be the key in POST.",
      'file_input_name' => "the name of the input field that will receive the file stream. Will be the key in FILES.",
      'csv_delimiter_input_name' => "the name of the input field that will receive the CSV field delimiter. Will be the key in FILES.",
   ];
   
   foreach($mandatory_variables as $var => $usage) {
      if( ! isset($$var)) {
         throw new Exception(sprintf(
            'Usage: You must provide a variable named: "%s" with value: %s',
            $var,
            $usage
         ));
      }
   }
   
   $chosen_plan = isset($chosen_plan) ? $chosen_plan : '';
   $form_action = isset($submit_url) ? ' action="'.htmlspecialchars($submit_url).'"' : '';
   $max_file_size = isset($max_file_size) && is_numeric($max_file_size) ? $max_file_size : '30000';
   $csv_delimiter = isset($csv_delimiter) && is_string($csv_delimiter) ? $csv_delimiter : ',';
   
   $html = [];
   
   $html[] = '<div class="plugin-etl__csvimport">';
      $html[] = '<div class="center plugin-etl__container">';
         $html[] = '<h1>';
            $html[] = __("Importation CSV");
         $html[] = '</h1>';
         
         $html[] = '<form';
          $html[] = ' class="plugin-etl_form"';
          $html[] = ' enctype="multipart/form-data"';
          $html[] = ' method="POST"';
          $html[] = $form_action;
         $html[] = '>';
            $html[] = '<input';
             $html[] = ' type="hidden"';
             $html[] = ' name="MAX_FILE_SIZE"';
             $html[] = ' value="'.htmlspecialchars($max_file_size).'"';
            $html[] = '/>';

            $html[] = '<div class="plugin-etl__input-group">';
               $html[] = '<label';
                $html[] = ' for="'.htmlspecialchars($plan_input_name).'"';
               $html[] = '>';
                  $html[] = __("Scénario d'importation").': ';
               $html[] = '</label>';
               $html[] = '<input';
                $html[] = ' type="text"';
                $html[] = ' name="'.htmlspecialchars($plan_input_name).'"';
                $html[] = ' value="'.htmlspecialchars($chosen_plan).'"';
                $html[] = ' required';
               $html[] = '/>';
            $html[] = '</div>';
            
            $html[] = '<div class="plugin-etl__input-group">';
               $html[] = '<label';
                $html[] = ' for="'.htmlspecialchars($file_input_name).'"';
               $html[] = '>';
                  $html[] = __("Fichier CSV").': ';
               $html[] = '</label>';
               $html[] = '<input';
                $html[] = ' name="'.htmlspecialchars($file_input_name).'"';
                $html[] = ' type="file"';
                $html[] = ' required';
               $html[] = '/>';
            $html[] = '</div>';

            $html[] = '<div class="plugin-etl__input-group">';
               $html[] = '<label';
                $html[] = ' for="'.htmlspecialchars($csv_delimiter_input_name).'"';
               $html[] = '>';
                  $html[] = __("Délimiteur").': ';
               $html[] = '</label>';
               $html[] = '<input';
                $html[] = ' type="text"';
                $html[] = ' name="'.htmlspecialchars($csv_delimiter_input_name).'"';
                $html[] = ' value="'.htmlspecialchars($csv_delimiter).'"';
                $html[] = ' required';
               $html[] = '/>';
            $html[] = '</div>';
            
            $html[] = '<div class="plugin-etl__form-actions">';
               $html[] = '<input';
                $html[] = ' class="submit"';
                $html[] = ' type="submit"';
                $html[] = ' value="'.__("Importer").'"';
               $html[] = '/>';
            $html[] = '</div>';
         
         //Use this function to get mandatory CSRF protection.
         //Argument "false": Don't echo, return string instead.
         $html[] = Html::closeForm(false);
      
      $html[] = '</div>';
   $html[] = '</div>';
   
   echo implode('', $html);
