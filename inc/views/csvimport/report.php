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

   $errors = isset($errors) ? $errors : [];
   $success_msg = isset($success_msg) ? $success_msg : __("Opération réussie.");
   $original_file_name = isset($original_file_name) ? $original_file_name : '';
   
   $html = [];
   
   $html[] = '<div class="plugin-etl__csvimport plugin-etl__csvimport__report">';
      $html[] = '<div class="plugin-etl__container">';
         $html[] = '<h1>';
            $html[] = __("Rapport d'importation CSV");
         $html[] = '</h1>';
         
         $html[] = '<div>';
            $html[] = '<b>';
               $html[] = __("Nom du fichier");
               $html[] = ': ';
            $html[] = '</b>';
            $html[] = htmlentities($original_file_name);
         $html[] = '</div>';
         
         if(count($errors)) {
            $html[] = '<div class="plugin-etl__section-error">';
               $html[] = '<h2>';
                  $html[] = __("Erreur(s) d'importation");
               $html[] = '</h2>';
               
               $html[] = '<table>';
                  foreach($errors as $error) {
                     $html[] = PluginETLView::toGenericHtmlTable($error);
                  }
               $html[] = '</table>';
            $html[] = '</div>';
            
         } else {
            $html[] = '<div class="plugin-etl_section-success">';
               $html[] = '<h2>';
                  $html[] = __("Succès");
               $html[] = '</h2>';
               $html[] = PluginETLView::toGenericHtml($success_msg, 'div', 'div');
            $html[] = '</div>';
         }
      
      $html[] = '</div>';
   $html[] = '</div>';
   
   echo implode('', $html);
