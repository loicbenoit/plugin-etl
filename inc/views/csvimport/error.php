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

   $errors = isset($errors) && is_array($errors) ? $errors : [];
   
   $html = [];
   
   $html[] = '<div class="plugin-etl__csvimport">';
      $html[] = '<div class="center plugin-etl__container">';
         $html[] = '<h1>';
            $html[] = __("Erreur(s)");
         $html[] = '</h1>';
         
         $html[] = '<div>';
            $html[] = '<pre>';
               $html[] = htmlspecialchars(var_export($errors, true));
            $html[] = '</pre>';
         $html[] = '</div>';
      
      $html[] = '</div>';
   $html[] = '</div>';
   
   echo implode('', $html);
