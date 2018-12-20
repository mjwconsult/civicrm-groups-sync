<!-- assets/templates/admin/settings-page.php -->
<div class="wrap">

	<h1><?php _e( 'CiviCRM Groups Sync', 'civicrm-groups-sync' ); ?></h1>

	<?php if ( $show_tabs ) : ?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo $urls['settings']; ?>" class="nav-tab nav-tab-active"><?php _e( 'Settings', 'civicrm-groups-sync' ); ?></a>
			<?php

			/**
			 * Allow others to add tabs.
			 *
			 * @since 0.1
			 *
			 * @param array $urls The array of subpage URLs.
			 * @param str The key of the active tab in the subpage URLs array.
			 */
			do_action( 'civicrm_groups_sync_settings_nav_tabs', $urls, 'settings' );

			?>
		</h2>
	<?php else : ?>
		<hr />
	<?php endif; ?>

	<form method="post" id="civicrm_groups_sync_settings_form" action="<?php echo $this->page_submit_url_get(); ?>">

		<?php wp_nonce_field( 'civicrm_groups_sync_settings_action', 'civicrm_groups_sync_settings_nonce' ); ?>

		<p><?php _e( 'Settings to go here.', 'civicrm-groups-sync' ) ?></p>

		<hr />

		<p class="submit">
			<input class="button-primary" type="submit" id="civicrm_groups_sync_settings_submit" name="civicrm_groups_sync_settings_submit" value="<?php _e( 'Save Changes', 'civicrm-groups-sync' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->



