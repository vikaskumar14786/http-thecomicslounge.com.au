<?php

/**
 * copyright (C) 2012 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class mod_jevents_slideshowInstallerScript {

        //
        // Joomla installer functions
        //
	public function preflight($type, $parent) {
                
                $jversion = new JVersion();
                // Installing component manifest file version
                $this->release = $parent->get( "manifest" )->version;
                
                // Manifest file minimum Joomla version
                $this->minimum_joomla_release = $parent->get( "manifest" )->attributes()->version;   
                
                // abort if the current Joomla release is older
                if( version_compare( $jversion->getShortVersion(), $this->minimum_joomla_release, 'lt' ) ) {
                        Jerror::raiseWarning(null, 'Cannot install JEvents Slideshow Module in a Joomla release prior to '.$this->minimum_joomla_release);
                        return false;
                }
        }

        function install($parent) {
                
        }

        function uninstall($parent) {
                // No nothing for now, we want to keep the tables just incase they remove the plugin by accident. 
        }

        function update($parent) {
                // Nothing to do for now, tables should be created on install.
        }

        function postflight($type, $parent) {

        }

}
