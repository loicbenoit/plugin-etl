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

/**
 * Permission management in profile
 * @todo Implement, use and test.
 */
class PluginETLProfile extends Profile
{

   static $rightname = "profile";

   static function getAllRights() {

      $rights = [
        //['itemtype'  => 'PluginETLModelJob',
        //      'label'     => __('ETL Job management', 'etl'),
        //      'field'     => 'plugin_datainjection_model'],
        ['itemtype'  => 'PluginETLModel',
              'label'     => __('Importation CSV', 'etl'),
              'field'     => 'plugin_etl_use',
              'rights'    => [READ => __('Read')]]];
      return $rights;
   }

    /**
    * Clean profiles_id from plugin's profile table
    *
    * @param $ID
   **/
   function cleanProfiles($ID) {

      global $DB;
      $query = "DELETE FROM `glpi_profiles`
                WHERE `profiles_id`='$ID'
                   AND `name` LIKE '%plugin_etl%'";
      $DB->query($query);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         if ($item->getField('interface') == 'central') {
            return __('ETL', 'etl');
         }
         return '';
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         $profile = new self();
         $ID   = $item->getField('id');
         //If ETL right is not set for this profile, create it
         self::addDefaultProfileInfos(
             $item->getID(),
             ['plugin_etl_model' => 0]
         );
         $profile->showForm($ID);
      }
      return true;
   }

    /**
    * @param $profile
   **/
   static function addDefaultProfileInfos($profiles_id, $rights) {

      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable(
             'glpi_profilerights',
             "`profiles_id`='$profiles_id' AND `name`='$right'"
         )) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

    /**
    * @param $ID  integer
    */
   static function createFirstAccess($profiles_id) {

      include_once GLPI_ROOT."/plugins/etl/inc/profile.class.php";
      foreach (self::getAllRights() as $right) {
         self::addDefaultProfileInfos(
             $profiles_id,
             ['plugin_etl_model' => ALLSTANDARDRIGHT,
             'plugin_etl_use' => READ]
         );
      }
   }

   static function migrateProfiles() {
      global $DB;
      if (!$DB->tableExists('glpi_plugin_etl_profiles')) {
         return true;
      }

      $profiles = getAllDatasFromTable('glpi_plugin_etl_profiles');
      foreach ($profiles as $id => $profile) {
         $query = "SELECT `id` FROM `glpi_profiles` WHERE `name`='".$profile['name']."'";
         $result = $DB->query($query);
         if ($DB->numrows($result) == 1) {
            $id = $DB->result($result, 0, 'id');
            switch ($profile['model']) {
               case 'r' :
                   $value = READ;
                break;
               case 'w':
                   $value = ALLSTANDARDRIGHT;
                break;
               case 0:
               default:
                  $value = 0;
                break;
            }
            self::addDefaultProfileInfos($id, ['plugin_etl_model' => $value]);
            if ($value > 0) {
               self::addDefaultProfileInfos($id, ['plugin_etl_use' => READ]);
            } else {
               self::addDefaultProfileInfos($id, ['plugin_etl_model' => 0]);
            }
         }
      }
   }

    /**
    * Show profile form
    *
    * @param $items_id integer id of the profile
    * @param $target value url of target
    *
    * @return nothing
    **/
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform
      ) {
         $profile = new Profile();
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = self::getAllRights();
      $profile->displayRightsChoiceMatrix(
         self::getAllRights(),
         [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => __('General')
         ]
      );
      if ($canedit && $closeform) {
         echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}
