<?php

/**
 * CiviCRM Groups Sync WordPress Class.
 *
 * A class that encapsulates functionality for interacting with the "Groups"
 * plugin in WordPress.
 *
 * @since 0.1
 */
class CiviCRM_Groups_Sync_WordPress {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;



	/**
	 * Class constructor.
	 *
	 * @since 0.1
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// store reference to plugin
		$this->plugin = $plugin;

		// Add action for init.
		add_action( 'civicrm_groups_sync_loaded', array( $this, 'initialise' ) );

	}



	//##########################################################################



	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Hook into group creation.
		add_action( 'groups_created_group', array( $this, 'group_created' ), 10 );

		// Hook into group updates.
		add_action( 'groups_updated_group', array( $this, 'group_updated' ), 10 );

		// Hook into group deletion.
		add_action( 'groups_deleted_group', array( $this, 'group_deleted' ), 10 );

		// Add option to Group add form.
		add_filter( 'groups_admin_groups_add_form_after_fields', array( $this, 'form_add_filter' ), 10 );

		// Add option to Group edit form.
		add_filter( 'groups_admin_groups_edit_form_after_fields', array( $this, 'form_edit_filter' ), 10, 2 );

		// Hook into form submission?
		//add_action( 'groups_admin_groups_add_submit_success', array( $this, 'form_submitted' ), 10 );
		//add_action( 'groups_admin_groups_edit_submit_success', array( $this, 'form_submitted' ), 10 );

		// Hook into user additions to a group.
		add_action( 'groups_created_user_group', array( $this, 'group_member_added' ), 10, 2 );

		// Hook into user deletions from a group.
		add_action( 'groups_deleted_user_group', array( $this, 'group_member_deleted' ), 10, 2 );

	}



	//##########################################################################



	/**
	 * Create a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param array $params The params used to create the group.
	 * @return int|bool $group_id The ID of the group, or false on failure.
	 */
	public function group_create( $params ) {

		// Bail if a group by that name exists.
		if ( ! empty( $params['name'] ) ) {
			$group = Groups_Group::read_by_name( $params['name'] );
			if ( ! empty( $group->group_id ) ) {
				return false;
			}
		}

		// Remove hook.
		remove_action( 'groups_created_group', array( $this, 'group_created' ), 10 );

		// Create the group.
		$group_id = Groups_Group::create( $params );

		// Reinstate hook.
		add_action( 'groups_created_group', array( $this, 'group_created' ), 10 );

		// --<
		return $group_id;

	}



	/**
	 * Update a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param array $params The params used to update the group.
	 * @return int|bool $group_id The ID of the group, or false on failure.
	 */
	public function group_update( $params ) {

		// Remove hook.
		remove_action( 'groups_updated_group', array( $this, 'group_updated' ), 10 );

		// Update the group.
		$group_id = Groups_Group::update( $params );

		// Reinstate hook.
		add_action( 'groups_updated_group', array( $this, 'group_updated' ), 10 );

		// --<
		return $group_id;

	}



	/**
	 * Delete a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the group to delete.
	 * @return int|bool $group_id The ID of the deleted group, or false on failure.
	 */
	public function group_delete( $group_id ) {

		// Remove hook.
		remove_action( 'groups_deleted_group', array( $this, 'group_deleted' ), 10 );

		// Delete the group.
		$group_id = Groups_Group::delete( $group_id );

		// Reinstate hook.
		add_action( 'groups_deleted_group', array( $this, 'group_deleted' ), 10 );

		// --<
		return $group_id;

	}



	//##########################################################################



	/**
	 * Intercept when a "Groups" group is created.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the new group.
	 */
	public function group_created( $group_id ) {

		// Bail if our checkbox was not checked.
		if ( ! $this->form_get_sync() ) return;

		// Get full group data.
		$group = Groups_Group::read( $group_id );

		// Create a synced CiviCRM group.
		$this->plugin->civicrm->group_create_from_wp_group( $group );

	}



	/**
	 * Intercept when a "Groups" group is updated.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the updated group.
	 */
	public function group_updated( $group_id ) {

		// Get full group data.
		$group = Groups_Group::read( $group_id );

		// Update the synced CiviCRM group.
		$this->plugin->civicrm->group_update_from_wp_group( $group );

	}



	/**
	 * Intercept when a "Groups" group is deleted.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the deleted group.
	 */
	public function group_deleted( $group_id ) {

		// Delete the synced CiviCRM group.
		$this->plugin->civicrm->group_delete_by_wp_id( $group_id );

	}



	//##########################################################################



	/**
	 * Create a "Groups" group from a CiviCRM group.
	 *
	 * @since 0.1
	 *
	 * @param object|array $civicrm_group The CiviCRM group data.
	 * @return int|bool $group_id The ID of the "Groups" group, or false on failure.
	 */
	public function group_create_from_civicrm_group( $civicrm_group ) {

		// Construct minimum "Groups" group params.
		if ( is_object( $civicrm_group ) ) {
			$params = array(
				'name' =>  isset( $civicrm_group->title ) ? $civicrm_group->title : __( 'Untitled', 'civicrm-groups-sync' ),
				'description' => isset( $civicrm_group->description ) ? $civicrm_group->description : '',
			);
		} else {
			$params = array(
				'name' => isset( $civicrm_group['title'] ) ? $civicrm_group['title'] : __( 'Untitled', 'civicrm-groups-sync' ),
				'description' => isset( $civicrm_group['description'] ) ? $civicrm_group['description'] : '',
			);
		}

		// Create it.
		$group_id = $this->group_create( $params );

		// --<
		return $group_id;

	}



	/**
	 * Update a "Groups" group from a CiviCRM group.
	 *
	 * @since 0.1
	 *
	 * @param object|array $civicrm_group The CiviCRM group data.
	 * @return int|bool $group_id The ID of the "Groups" group, or false on failure.
	 */
	public function group_update_from_civicrm_group( $civicrm_group ) {

		// Construct "Groups" group params.
		if ( is_object( $civicrm_group ) ) {

			// Init params.
			$params = array(
				'name' =>  isset( $civicrm_group->title ) ? $civicrm_group->title : __( 'Untitled', 'civicrm-groups-sync' ),
				'description' => isset( $civicrm_group->description ) ? $civicrm_group->description : '',
			);

			// Get source string.
			$source = isset( $civicrm_group->source ) ? $civicrm_group->source : '';

		} else {

			// Init params.
			$params = array(
				'name' => isset( $civicrm_group['title'] ) ? $civicrm_group['title'] : __( 'Untitled', 'civicrm-groups-sync' ),
				'description' => isset( $civicrm_group['description'] ) ? $civicrm_group['description'] : '',
			);

			// Get source string.
			$source = isset( $civicrm_group['source'] ) ? $civicrm_group['source'] : '';

		}

		// Sanity check source.
		if ( empty( $source ) ) {
			return false;
		}

		// Get ID from source string.
		$tmp = explode( 'synced-group-', $source );
		$wp_group_id = isset( $tmp[1] ) ? absint( trim( $tmp[1] ) ) : false;

		// Sanity check.
		if ( empty( $wp_group_id ) ) {
			return false;
		}

		// Add ID to params.
		$params['group_id'] = $wp_group_id;

		// Update the group.
		$group_id = $this->group_update( $params );

		// --<
		return $group_id;

	}



	/**
	 * Delete a "Groups" group using a CiviCRM group ID.
	 *
	 * @since 0.1
	 *
	 * @param int $civicrm_group_id The ID of the CiviCRM group.
	 * @return int|bool $group_id The ID of the deleted "Groups" group, or false on failure.
	 */
	public function group_delete_by_civicrm_group_id( $civicrm_group_id ) {

		// Get the ID of the "Groups" group.
		$wp_group_id = $this->plugin->civicrm->group_get_wp_id_by_civicrm_id( $civicrm_group_id );

		// Sanity check.
		if ( empty( $wp_group_id ) ) {
			return false;
		}

		// Delete the group.
		$group_id = $this->group_delete( $wp_group_id );

		// --<
		return $group_id;

	}



	//##########################################################################



	/**
	 * Get a "Groups" group's admin URL.
	 *
	 * @since 0.1.1
	 *
	 * @param int $group_id The numeric ID of the "Groups" group.
	 * @return str $group_url The "Groups" group's admin URL.
	 */
	public function group_get_url( $group_id ) {

		// Get group admin URL.
		$group_url = admin_url( 'admin.php?page=groups-admin&group_id=' . $group_id . '&action=edit' );

		/**
		 * Filter the URL of the "Groups" group's admin page.
		 *
		 * @since 0.1.1
		 *
		 * @param str $group_url The existing URL.
		 * @param int $group_id The numeric ID of the CiviCRM group.
		 * @return str $group_url The modified URL.
		 */
		return apply_filters( 'civicrm_groups_sync_group_get_url_wp', $group_url, $group_id );

	}



	//##########################################################################



	/**
	 * Get a "Groups" group using a CiviCRM group ID.
	 *
	 * @since 0.1
	 *
	 * @param int $civicrm_group_id The ID of the CiviCRM group.
	 * @return array|bool $wp_group The "Groups" group data, or false on failure.
	 */
	public function group_get_by_civicrm_id( $civicrm_group_id ) {

		// Get the ID of the "Groups" group.
		$wp_group_id = $this->plugin->civicrm->group_get_wp_id_by_civicrm_id( $civicrm_group_id );

		// Sanity check.
		if ( empty( $wp_group_id ) ) {
			return false;
		}

		// Get full group data.
		$wp_group = Groups_Group::read( $wp_group_id );

		// --<
		return $wp_group;

	}



	/**
	 * Get a "Groups" group ID using a CiviCRM group ID.
	 *
	 * @since 0.1
	 *
	 * @param int $civicrm_group_id The ID of the CiviCRM group.
	 * @return int|bool $group_id The "Groups" group ID, or false on failure.
	 */
	public function group_get_wp_id_by_civicrm_id( $civicrm_group_id ) {

		// Get the "Groups" group.
		$wp_group = $this->group_get_by_civicrm_id( $civicrm_group_id );

		// Sanity check group.
		if ( empty( $wp_group ) ) {
			return false;
		}

		// Sanity check group ID.
		if ( empty( $wp_group->group_id ) ) {
			return false;
		}

		// --<
		return $wp_group->group_id;

	}



	//##########################################################################



	/**
	 * Filter the Add Group form.
	 *
	 * @since 0.1
	 *
	 * @param str $content The existing content to be inserted after the default fields.
	 * @return str $content The modified content to be inserted after the default fields.
	 */
	public function form_add_filter( $content ) {

		// Start buffering.
		ob_start();

		// Include template.
		include( CIVICRM_GROUPS_SYNC_PATH . 'assets/templates/admin/settings-groups-create.php' );

		// Save the output and flush the buffer.
		$field = ob_get_clean();

		// Add field to form.
		$content .= $field;

		// --<
		return $content;

	}



	/**
	 * Filter the Edit Group form.
	 *
	 * @since 0.1
	 *
	 * @param str $content The existing content to be inserted after the default fields.
	 * @param int $group_id The numeric ID of the group.
	 * @return str $content The modified content to be inserted after the default fields.
	 */
	public function form_edit_filter( $content, $group_id ) {

		// Get existing CiviCRM group.
		$civicrm_group = $this->plugin->civicrm->group_get_by_wp_id( $group_id );

		// Bail if there isn't one.
		if ( $civicrm_group === false ) {
			return $content;
		}

		// Get CiviCRM group admin URL for template.
		$group_url = $this->plugin->civicrm->group_get_url( $civicrm_group['id'] );

		// Start buffering.
		ob_start();

		// Include template.
		include( CIVICRM_GROUPS_SYNC_PATH . 'assets/templates/admin/settings-groups-edit.php' );

		// Save the output and flush the buffer.
		$field = ob_get_clean();

		// Add field to form.
		$content .= $field;

		// --<
		return $content;

	}



	/**
	 * Get our Group form variable.
	 *
	 * @since 0.1.1
	 *
	 * @return bool $sync True if the group should be synced, false otherwise.
	 */
	public function form_get_sync() {

		// Do not sync by default.
		$sync = false;

		// Maybe override if our POST variable is set.
		if ( isset( $_POST['civicrm-group-field'] ) AND $_POST['civicrm-group-field'] == 1 ) {
			$sync = true;
		}

		// --<
		return $sync;

	}



	/**
	 * Intercept successful Group form submission.
	 *
	 * Unfortunately for our purposes, this callback is triggered after the
	 * group has been created. We therefore have to check for our POST variable
	 * in `group_created`, `group_updated` and `group_deleted` instead.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The numeric ID of the group.
	 */
	public function form_submitted( $group_id ) {

		/*
		$e = new Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'group_id' => $group_id,
			//'backtrace' => $trace,
		), true ) );
		*/

	}



	//##########################################################################



	/**
	 * Add a WordPress user to a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The ID of the WordPress user to add to the group.
	 * @param int $group_id The ID of the "Groups" group.
	 * @return bool $success True on success, false otherwise.
	 */
	public function group_member_add( $user_id, $group_id ) {

		// Bail if they are already a group member.
		if ( Groups_User_Group::read( $user_id, $group_id ) ) {
			return true;
		}

		// Remove hook.
		remove_action( 'groups_created_user_group', array( $this, 'group_member_added' ), 10 );

		// Add user to group.
		$success = Groups_User_Group::create( array(
			'user_id'  => $user_id,
			'group_id' => $group_id,
		));

		// Reinstate hook.
		add_action( 'groups_created_user_group', array( $this, 'group_member_added' ), 10, 2 );

		// Maybe log on failure?
		if ( ! $success ) {
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => __( 'Could not add user to group.', 'civicrm-groups-sync' ),
				'user_id' => $user_id,
				'group_id' => $group_id,
				'backtrace' => $trace,
			), true ) );
		}

		// --<
		return $success;

	}



	/**
	 * Delete a WordPress user from a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The ID of the WordPress user to delete from the group.
	 * @param int $group_id The ID of the "Groups" group.
	 * @return bool $success True on success, false otherwise.
	 */
	public function group_member_delete( $user_id, $group_id ) {

		// Bail if they are not a group member.
		if ( ! Groups_User_Group::read( $user_id, $group_id ) ) {
			return true;
		}

		// Remove hook.
		remove_action( 'groups_deleted_user_group', array( $this, 'group_member_deleted' ), 10 );

		// Delete user from group.
		$success = Groups_User_Group::delete( $user_id, $group_id );

		// Reinstate hook.
		add_action( 'groups_deleted_user_group', array( $this, 'group_member_deleted' ), 10, 2 );

		// Maybe log on failure?
		if ( ! $success ) {
			$e = new Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => __( 'Could not delete user from group.', 'civicrm-groups-sync' ),
				'user_id' => $user_id,
				'group_id' => $group_id,
				'backtrace' => $trace,
			), true ) );
		}

		// --<
		return $success;

	}



	//##########################################################################



	/**
	 * Intercept when a WordPress user is added to a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The ID of the WordPress user added to the group.
	 * @param int $group_id The ID of the "Groups" group.
	 */
	public function group_member_added( $user_id, $group_id ) {

		// Get contact for this user ID.
		$civicrm_contact_id = $this->plugin->civicrm->contact_id_get_by_user_id( $user_id );

		// Bail if we don't get one.
		if ( empty( $civicrm_contact_id ) OR $civicrm_contact_id === false ) {
			return;
		}

		// Get CiviCRM group for this "Groups" group ID.
		$civicrm_group = $this->plugin->civicrm->group_get_by_wp_id( $group_id );

		// Bail if we don't get one.
		if ( empty( $civicrm_group ) OR $civicrm_group === false ) {
			return;
		}

		// Add user to CiviCRM group.
		$success = $this->plugin->civicrm->group_contact_create( $civicrm_group['id'], $civicrm_contact_id );

	}



	/**
	 * Intercept when a WordPress user is deleted from a "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The ID of the WordPress user added to the group.
	 * @param int $group_id The ID of the "Groups" group.
	 */
	public function group_member_deleted( $user_id, $group_id ) {

		// Get contact for this user ID.
		$civicrm_contact_id = $this->plugin->civicrm->contact_id_get_by_user_id( $user_id );

		// Bail if we don't get one.
		if ( empty( $civicrm_contact_id ) OR $civicrm_contact_id === false ) {
			return;
		}

		// Get CiviCRM group for this "Groups" group ID.
		$civicrm_group = $this->plugin->civicrm->group_get_by_wp_id( $group_id );

		// Bail if we don't get one.
		if ( empty( $civicrm_group ) OR $civicrm_group === false ) {
			return;
		}

		// Remove user from CiviCRM group.
		$success = $this->plugin->civicrm->group_contact_delete( $civicrm_group['id'], $civicrm_contact_id );

	}



	//##########################################################################



	/**
	 * Get a WordPress user ID for a given CiviCRM contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric CiviCRM contact ID.
	 * @return int|bool $user The WordPress user ID, or false on failure.
	 */
	public function user_id_get_by_contact_id( $contact_id ) {

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Make sure CiviCRM file is included.
		require_once( 'CRM/Core/BAO/UFMatch.php' );

		// Search using CiviCRM's logic.
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $contact_id );

		// Cast user ID as boolean if we didn't get one.
		if ( empty( $user_id ) ) {
			$user_id = false;
		}

		/**
		 * Filter the result of the WordPress user lookup.
		 *
		 * You can use this filter to create a WordPress user if none is found.
		 * Return the new WordPress user ID and the group linkage will be made.
		 *
		 * @since 0.1
		 *
		 * @param int|bool $user_id The numeric ID of the WordPress user, or false on failure.
		 * @param int $contact_id The numeric ID of the CiviCRM contact.
		 * @return int|bool $user_id The numeric ID of the WordPress user, or false on failure.
		 */
		$user_id = apply_filters( 'civicrm_groups_sync_user_id_get_by_contact_id', $user_id, $contact_id );

		// --<
		return $user_id;

	}



	/**
	 * Get a WordPress user object for a given CiviCRM contact ID.
	 *
	 * @since 0.1
	 *
	 * @param int $contact_id The numeric CiviCRM contact ID.
	 * @return WP_User|bool $user The WordPress user object, or false on failure.
	 */
	public function user_get_by_contact_id( $contact_id ) {

		// Get WordPress user ID.
		$user_id = $this->user_id_get_by_contact_id( $contact_id );

		// Bail if we didn't get one.
		if ( empty( $user_id ) OR $user_id === false ) {
			return false;
		}

		// Get user object.
		$user = new WP_User( $user_id );

		// Bail if we didn't get one.
		if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) {
			return false;
		}

		// --<
		return $user;

	}



} // Class ends.
