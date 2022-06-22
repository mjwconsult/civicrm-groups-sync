<?php
/**
 * Uninstaller.
 *
 * Handles plugin uninstallation.
 *
 * @package CiviCRM_Groups_Sync
 * @since 0.1
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
