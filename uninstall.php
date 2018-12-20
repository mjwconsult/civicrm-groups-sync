<?php /*
================================================================================
CiviCRM Groups Sync Uninstaller
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====


--------------------------------------------------------------------------------
*/



// Kick out if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}



// TODO: This may need to be done for every site in multisite.

// Delete version.
delete_option( 'civicrm_groups_sync_version' );

// Delete settings.
delete_option( 'civicrm_groups_sync_settings' );
