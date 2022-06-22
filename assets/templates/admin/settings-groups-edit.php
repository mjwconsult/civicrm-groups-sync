<?php
/**
 * Edit group template.
 *
 * Handles markup for the Edit group screen.
 *
 * @package CiviCRM_Groups_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?><!-- assets/templates/admin/settings-groups-edit.php -->
<div class="field">
	<p>
	<?php

	echo sprintf(
		/* translators: %s: The URL of the Group. */
		__( 'There is <a href="%s">an existing CiviCRM group</a> that is linked to this group. The group members will exist in both groups.', 'civicrm-groups-sync' ),
		$group_url
	);

	?>
	</p>
</div>
