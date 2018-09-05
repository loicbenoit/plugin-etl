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
 * An exception for displaying feedback messages to the frontend.
 *
 * SECURITY NOTICE: The exception message WILL be displayed in the frontend.
 */
class FrontendFeedbackException extends \Exception
{
   
}