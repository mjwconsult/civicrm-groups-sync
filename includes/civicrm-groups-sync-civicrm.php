<?php

/**
 * CiviCRM Groups Sync CiviCRM Class.
 *
 * A class that encapsulates CiviCRM functionality.
 *
 * @since 0.1
 */
class CiviCRM_Groups_Sync_CiviCRM {

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

		// Register template directory for form amends.
		add_action( 'civicrm_config', array( $this, 'register_form_directory' ), 10 );

		// Modify CiviCRM group create form.
		add_action( 'civicrm_buildForm', array( $this, 'form_group_create_build' ), 10, 2 );

		// Intercept CiviCRM group create form submission process.
		add_action( 'civicrm_postProcess', array( $this, 'form_group_create_process' ), 10, 2 );

		// Intercept before and after CiviCRM creating a group.
		add_action( 'civicrm_pre', array( $this, 'group_created_pre' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'group_created_post' ), 10, 4 );

		// Intercept after CiviCRM updated a group.
		add_action( 'civicrm_post', array( $this, 'group_updated' ), 10, 4 );

		// Intercept CiviCRM's add contacts to group.
		add_action( 'civicrm_pre', array( $this, 'group_contacts_added' ), 10, 4 );

		// Intercept CiviCRM's delete contacts from group.
		add_action( 'civicrm_pre', array( $this, 'group_contacts_deleted' ), 10, 4 );

		// Intercept CiviCRM's rejoin contacts to group.
		add_action( 'civicrm_pre', array( $this, 'group_contacts_rejoined' ), 10, 4 );

	}



	//##########################################################################



	/**
	 * Register directory that CiviCRM searches in for our form template file.
	 *
	 * @since 0.1
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_form_directory( &$config ) {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return;

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Define our custom path.
		$custom_path = CIVICRM_GROUPS_SYNC_PATH . 'assets/templates/civicrm';

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directory.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Enable a Groups group to be created when creating a CiviCRM group.
	 *
	 * @since 0.1
	 *
	 * @param string $formName The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_group_create_build( $formName, &$form ) {

		// Is this the group edit form?
		if ( $formName != 'CRM_Group_Form_Edit' ) return;

		// Get CiviCRM group.
		$civicrm_group = $form->getVar( '_group' );

		// Assign template depending on whether we have a group.
		if ( isset( $civicrm_group ) AND ! empty( $civicrm_group ) ) {

			// It's the edit group form.

			// Get the Groups group ID.
			$wp_group_id = $this->group_get_wp_id_by_civicrm_id( $civicrm_group->id );

			// Bail if there isn't one.
			if ( $wp_group_id === false ) {
				return;
			}

			// Get the URL.
			$group_url = $this->plugin->wordpress->group_get_url( $wp_group_id );

			// Add the field element to the form.
			$form->add( 'html', 'civicrm_groups_sync_edit', __( 'Existing Synced Group', 'civicrm-groups-sync' ) );

			// Add URL.
			$form->assign('civicrm_groups_sync_edit_url', $group_url);

			// Insert template block into the page.
			CRM_Core_Region::instance('page-body')->add( array(
				'template' => 'civicrm-groups-sync-edit.tpl'
			));

		} else {

			// It's the new group form.

			// Add the field element to the form.
			$form->add( 'checkbox', 'civicrm_groups_sync_create', __( 'Create Synced Group', 'civicrm-groups-sync' ) );

			// Insert template block into the page.
			CRM_Core_Region::instance('page-body')->add( array(
				'template' => 'civicrm-groups-sync-create.tpl'
			));

		}

	}



	/**
	 * Callback for the Add Group form's postProcess hook.
	 *
	 * Curious (to me at least) that "civicrm_pre" and "civicrm_post" fire
	 * before this hook. Moreover, our checkbox value (when present) is passed
	 * to "civicrm_pre" as part of the "objectRef" array. Which means I'm not
	 * quite sure what to do here - or what the postProcess hook is useful for!
	 *
	 * @since 0.1
	 *
	 * @param string $formName The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_group_create_process( $formName, &$form ) {

		// Kick out if not group edit form.
		if ( ! ( $form instanceof CRM_Group_Form_Edit ) ) return;

		// Inspect submitted values.
		$values = $form->getVar( '_submitValues' );

		// Was our checkbox ticked?
		if ( ! isset( $values['civicrm_groups_sync_create'] ) ) return;
		if ( $values['civicrm_groups_sync_create'] != '1' ) return;

		// What now?

	}



	//##########################################################################



	/**
	 * Intercept when a CiviCRM group is about to be created.
	 *
	 * We update the params by which the CiviCRM group is created if our form
	 * element has been checked.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $civicrm_group The array of CiviCRM group data.
	 */
	public function group_created_pre( $op, $object_name, $civicrm_group_id, &$civicrm_group ) {

		// Target our operation.
		if ( $op != 'create' ) return;

		// Target our object type.
		if ( $object_name != 'Group' ) return;

		// Was our checkbox ticked?
		if ( ! isset( $civicrm_group['civicrm_groups_sync_create'] ) ) return;
		if ( $civicrm_group['civicrm_groups_sync_create'] != '1' ) return;

		// Always make the group of type "Access Control".
		if ( isset( $civicrm_group['group_type'] ) AND is_array( $civicrm_group['group_type'] ) ) {
			$civicrm_group['group_type'][1] = 1;
		} else {
			$civicrm_group['group_type'] = array( 1 => 1 );
		}

		// Use the "source" field to denote a "Synced Group".
		$civicrm_group['source'] = 'synced-group';

		// Set flag to trigger sync after database insert is complete.
		$this->sync_please = true;

	}



	/**
	 * Intercept after a CiviCRM group has been created.
	 *
	 * We create the "Groups" group and update the "source" field for the
	 * CiviCRM group with the ID of the "Groups" group.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $civicrm_group The array of CiviCRM Group data.
	 */
	public function group_created_post( $op, $object_name, $civicrm_group_id, $civicrm_group ) {

		// Target our operation.
		if ( $op != 'create' ) return;

		// Target our object type.
		if ( $object_name != 'Group' ) return;

		// Make sure we have a group.
		if ( ! ( $civicrm_group instanceOf CRM_Contact_BAO_Group ) ) return;

		// Check for our flag.
		if ( ! isset( $this->sync_please ) ) return;
		if ( $this->sync_please !== true ) return;

		// Bail if the "source" field is not set.
		if ( ! isset( $civicrm_group->source ) ) return;
		if ( $civicrm_group->source != 'synced-group' ) return;

		// Create a "Groups" group from CiviCRM group data.
		$wp_group_id = $this->plugin->wordpress->group_create_from_civicrm_group( $civicrm_group );

		// Remove hooks.
		remove_action( 'civicrm_pre', array( $this, 'group_created_pre' ), 10 );
		remove_action( 'civicrm_post', array( $this, 'group_created_post' ), 10 );

		// Update the "source" field to include the ID of the WordPress Group.
		$result = civicrm_api( 'Group', 'create', array(
			'version' => 3,
			'id' => $civicrm_group->id,
			'source' => 'synced-group-' . $wp_group_id,
		));

		// Reinstate hooks.
		add_action( 'civicrm_pre', array( $this, 'group_created_pre' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'group_created_post' ), 10, 4 );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'op' => $op,
				'object_name' => $object_name,
				'objectId' => $civicrm_group_id,
				'objectRef' => $civicrm_group,
				'result' => $result,
			), true ) );
		}

	}



	/**
	 * Intercept when a CiviCRM group has been updated.
	 *
	 * There seems to be a bug in CiviCRM such that "source" is not included in
	 * the $civicrm_group data that is passed to this callback. I assume it's
	 * because the "source" field can only be updated via code or the API, so
	 * it's excluded from the database update query.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $civicrm_group The array of CiviCRM Group data.
	 */
	public function group_updated( $op, $object_name, $civicrm_group_id, $civicrm_group ) {

		// Target our operation.
		if ( $op != 'edit' ) return;

		// Target our object type.
		if ( $object_name != 'Group' ) return;

		// Make sure we have a group.
		if ( ! ( $civicrm_group instanceOf CRM_Contact_BAO_Group ) ) return;

		// Get the full CiviCRM group.
		$civicrm_group_data = civicrm_api( 'Group', 'getsingle', array(
			'version' => 3,
			'id' => $civicrm_group_id,
		));

		// Bail on failure.
		if ( isset( $civicrm_group_data['is_error'] ) AND $civicrm_group_data['is_error'] == '1' ) {
			return;
		}

		// Bail if the "source" field is not set.
		if ( ! isset( $civicrm_group_data['source'] ) ) return;

		// Bail if the "source" field is not for a synced group.
		if ( false === strpos( $civicrm_group_data['source'], 'synced-group' ) ) return;

		// Update the "Groups" group from CiviCRM group data.
		$wp_group_id = $this->plugin->wordpress->group_update_from_civicrm_group( $civicrm_group_data );

	}



	/**
	 * Intercept a CiviCRM group prior to it being deleted.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $civicrm_group The array of CiviCRM group data.
	 */
	public function group_deleted_pre( $op, $object_name, $civicrm_group_id, &$civicrm_group ) {

		// Target our operation.
		if ( $op != 'delete' ) return;

		// Target our object type.
		if ( $object_name != 'Group' ) return;

	}



	//##########################################################################



	/**
	 * Get a CiviCRM group's admin URL.
	 *
	 * @since 0.1.1
	 *
	 * @param int $group_id The numeric ID of the CiviCRM group.
	 * @return str $group_url The CiviCRM group's admin URL.
	 */
	public function group_get_url( $group_id ) {

		// Kick out if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) return '';

		// Get group URL.
		$group_url = CRM_Utils_System::url( 'civicrm/group', 'reset=1&action=update&id=' . $group_id );

		/**
		 * Filter the URL of the CiviCRM group's admin page.
		 *
		 * @since 0.1.1
		 *
		 * @param str $group_url The existing URL.
		 * @param int $group_id The numeric ID of the CiviCRM group.
		 * @return str $group_url The modified URL.
		 */
		return apply_filters( 'civicrm_groups_sync_group_get_url_civi', $group_url, $group_id );

	}



	//##########################################################################



	/**
	 * Get a CiviCRM group by its ID.
	 *
	 * @since 0.1
	 *
	 * @param int $wp_group_id The numeric ID of the group.
	 * @return array|bool $civicrm_group The CiviCRM group data array, or false on failure.
	 */
	public function group_get_by_id( $group_id ) {

		// Get the CiviCRM group.
		$civicrm_group = civicrm_api( 'Group', 'getsingle', array(
			'version' => 3,
			'id' => $group_id,
		));

		// Bail on failure.
		if ( isset( $civicrm_group['is_error'] ) AND $civicrm_group['is_error'] == '1' ) {
			return false;
		}

		// Return group.
		return $civicrm_group;

	}



	/**
	 * Get a CiviCRM group using a "Groups" group ID.
	 *
	 * @since 0.1
	 *
	 * @param int $wp_group_id The numeric ID of the "Groups" group.
	 * @return array|bool $civicrm_group The CiviCRM group data array, or false on failure.
	 */
	public function group_get_by_wp_id( $wp_group_id ) {

		// Get the synced CiviCRM group.
		$civicrm_group = civicrm_api( 'Group', 'getsingle', array(
			'version' => 3,
			'source' => 'synced-group-' . $wp_group_id,
		));

		// Bail on failure.
		if ( isset( $civicrm_group['is_error'] ) AND $civicrm_group['is_error'] == '1' ) {
			return false;
		}

		// Return group.
		return $civicrm_group;

	}



	/**
	 * Get the "Groups" group ID using a CiviCRM group ID.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The numeric ID of the CiviCRM group.
	 * @return int|bool $wp_group_id The ID of the "Groups" group, or false on failure.
	 */
	public function group_get_wp_id_by_civicrm_id( $group_id ) {

		// Get the synced CiviCRM group.
		$civicrm_group = civicrm_api( 'Group', 'getsingle', array(
			'version' => 3,
			'id' => $group_id,
		));

		// Bail on failure.
		if ( isset( $civicrm_group['is_error'] ) AND $civicrm_group['is_error'] == '1' ) {
			return false;
		}

		// Bail if there's no "source" field.
		if ( empty( $civicrm_group['source'] ) ) {
			return false;
		}

		// Get ID from source string.
		$tmp = explode( 'synced-group-', $civicrm_group['source'] );
		$wp_group_id = isset( $tmp[1] ) ? absint( trim( $tmp[1] ) ) : false;

		// Return the ID of the "Groups" group.
		return $wp_group_id;

	}



	/**
	 * Create a CiviCRM group using a "Groups" group object.
	 *
	 * @since 0.1
	 *
	 * @param object $wp_group The "Groups" group object.
	 * @return int|bool $group_id The ID of the group, or false on failure.
	 */
	public function group_create_from_wp_group( $wp_group ) {

		// Sanity check.
		if ( ! is_object( $wp_group ) ) {
			return false;
		}

		// Remove hooks.
		remove_action( 'civicrm_pre', array( $this, 'group_created_pre' ), 10 );
		remove_action( 'civicrm_post', array( $this, 'group_created_post' ), 10 );

		// Create the synced CiviCRM group.
		$result = civicrm_api( 'Group', 'create', array(
			'version' => 3,
			'name' => wp_unslash( $wp_group->name ),
			'title' => wp_unslash( $wp_group->name ),
			'description' => isset( $wp_group->description ) ? wp_unslash( $wp_group->description ) : '',
			'group_type' => array( 1 => 1 ),
			'source' => 'synced-group-' . $wp_group->group_id,
		));

		// Reinstate hooks.
		add_action( 'civicrm_pre', array( $this, 'group_created_pre' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'group_created_post' ), 10, 4 );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'wp_group' => $wp_group,
				'result' => $result,
			), true ) );
			return false;
		}

		// Return new group ID.
		return absint( $result['id'] );

	}



	/**
	 * Update a CiviCRM group using a "Groups" group object.
	 *
	 * @since 0.1
	 *
	 * @param object $wp_group The "Groups" group object.
	 * @return int|bool $group_id The ID of the group, or false on failure.
	 */
	public function group_update_from_wp_group( $wp_group ) {

		// Sanity check.
		if ( ! is_object( $wp_group ) ) {
			return false;
		}

		// Get the synced CiviCRM group.
		$civicrm_group = $this->group_get_by_wp_id( $wp_group->group_id );

		// Sanity check.
		if ( $civicrm_group === false OR empty( $civicrm_group['id'] ) ) {
			return false;
		}

		// Remove hook.
		remove_action( 'civicrm_post', array( $this, 'group_updated' ), 10 );

		// Update the synced CiviCRM group.
		$result = civicrm_api( 'Group', 'create', array(
			'version' => 3,
			'id' => $civicrm_group['id'],
			'name' => wp_unslash( $wp_group->name ),
			'title' => wp_unslash( $wp_group->name ),
			'description' => isset( $wp_group->description ) ? wp_unslash( $wp_group->description ) : '',
		));

		// Reinstate hook.
		add_action( 'civicrm_post', array( $this, 'group_updated' ), 10, 4 );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'wp_group' => $wp_group,
				'civicrm_group' => $civicrm_group,
				'result' => $result,
			), true ) );
			return false;
		}

		// --<
		return absint( $civicrm_group['id'] );

	}



	/**
	 * Delete a CiviCRM group using a "Groups" group ID.
	 *
	 * @since 0.1
	 *
	 * @param int $wp_group_id The numeric ID of the "Groups" group.
	 * @return int|bool $group_id The ID of the group, or false on failure.
	 */
	public function group_delete_by_wp_id( $wp_group_id ) {

		// Get the synced CiviCRM group.
		$civicrm_group = $this->group_get_by_wp_id( $wp_group_id );

		// Sanity check.
		if ( $civicrm_group === false OR empty( $civicrm_group['id'] ) ) {
			return false;
		}

		// Delete the synced CiviCRM group.
		$result = civicrm_api( 'Group', 'delete', array(
			'version' => 3,
			'id' => $civicrm_group['id'],
		));

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'wp_group_id' => $wp_group_id,
				'civicrm_group' => $civicrm_group,
				'result' => $result,
			), true ) );
			return false;
		}

		// --<
		return absint( $civicrm_group['id'] );

	}



	//##########################################################################



	/**
	 * Add a CiviCRM contact to a CiviCRM group.
	 *
	 * @since 0.1
	 *
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $civicrm_contact_id The numeric ID of a CiviCRM contact.
	 * @return array|bool $result The group-contact data, or false on failure.
	 */
	public function group_contact_create( $civicrm_group_id, $civicrm_contact_id ) {

		// Remove hook.
		remove_action( 'civicrm_pre', array( $this, 'group_contacts_added' ), 10 );

		// Call API.
		$result = civicrm_api( 'GroupContact', 'create', array(
			'version' => 3,
			'group_id' => $civicrm_group_id,
			'contact_id' => $civicrm_contact_id,
			'status' => 'Added',
		));

		// Reinstate hook.
		add_action( 'civicrm_pre', array( $this, 'group_contacts_added' ), 10, 4 );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'civicrm_group_id' => $civicrm_group_id,
				'civicrm_contact_id' => $civicrm_contact_id,
				'result' => $result,
			), true ) );
			return false;
		}

		// --<
		return $result;

	}



	/**
	 * Delete a CiviCRM contact from a CiviCRM group.
	 *
	 * @since 0.1
	 *
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $civicrm_contact_id The numeric ID of a CiviCRM contact.
	 * @return array|bool $result The group-contact data, or false on failure.
	 */
	public function group_contact_delete( $civicrm_group_id, $civicrm_contact_id ) {

		// Remove hook.
		remove_action( 'civicrm_pre', array( $this, 'group_contacts_deleted' ), 10 );

		// Call API.
		$result = civicrm_api( 'GroupContact', 'create', array(
			'version' => 3,
			'group_id' => $civicrm_group_id,
			'contact_id' => $civicrm_contact_id,
			'status' => 'Removed',
		));

		// Reinstate hooks.
		add_action( 'civicrm_pre', array( $this, 'group_contacts_deleted' ), 10, 4 );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'civicrm_group_id' => $civicrm_group_id,
				'civicrm_contact_id' => $civicrm_contact_id,
				'result' => $result,
			), true ) );
			return false;
		}

		// --<
		return $result;

	}



	//##########################################################################



	/**
	 * Intercept when a CiviCRM contact is added to a group.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $contact_ids The array of CiviCRM Contact IDs.
	 */
	public function group_contacts_added( $op, $object_name, $civicrm_group_id, $contact_ids ) {

		// Target our operation.
		if ( $op != 'create' ) return;

		// Target our object type.
		if ( $object_name != 'GroupContact' ) return;

		// Get "Groups" group ID.
		$wp_group_id = $this->group_get_wp_id_by_civicrm_id( $civicrm_group_id );

		// Sanity check.
		if ( $wp_group_id === false ) {
			return;
		}

		// Loop through added contacts.
		if ( count( $contact_ids ) > 0 ) {
			foreach( $contact_ids AS $contact_id ) {

				// Get WordPress user ID.
				$user_id = $this->plugin->wordpress->user_id_get_by_contact_id( $contact_id );

				// Add user to "Groups" group.
				if ( $user_id !== false ) {
					$this->plugin->wordpress->group_member_add( $user_id, $wp_group_id );
				}

			}
		}

	}



	/**
	 * Intercept when a CiviCRM contact is deleted (or removed) from a group.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $contact_ids Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_deleted( $op, $object_name, $civicrm_group_id, $contact_ids ) {

		// Target our operation.
		if ( $op != 'delete' ) return;

		// Target our object type.
		if ( $object_name != 'GroupContact' ) return;

		// Get "Groups" group ID.
		$wp_group_id = $this->group_get_wp_id_by_civicrm_id( $civicrm_group_id );

		// Sanity check.
		if ( $wp_group_id === false ) {
			return;
		}

		// Loop through deleted contacts.
		if ( count( $contact_ids ) > 0 ) {
			foreach( $contact_ids AS $contact_id ) {

				// Get WordPress user ID.
				$user_id = $this->plugin->wordpress->user_id_get_by_contact_id( $contact_id );

				// Delete user from "Groups" group.
				if ( $user_id !== false ) {
					$this->plugin->wordpress->group_member_delete( $user_id, $wp_group_id );
				}

			}
		}

	}



	/**
	 * Intercept when a CiviCRM contact is re-added to a group.
	 *
	 * The issue here is that CiviCRM fires 'civicrm_pre' with $op = 'delete' regardless
	 * of whether the contact is being removed or deleted. If a contact is later re-added
	 * to the group, then $op != 'create', so we need to intercept $op = 'edit'.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $object_name The type of object.
	 * @param integer $civicrm_group_id The ID of the CiviCRM group.
	 * @param array $contact_ids Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_rejoined( $op, $object_name, $civicrm_group_id, $contact_ids ) {

		// Target our operation.
		if ( $op != 'edit' ) return;

		// Target our object type.
		if ( $object_name != 'GroupContact' ) return;

		// Get "Groups" group ID.
		$wp_group_id = $this->group_get_wp_id_by_civicrm_id( $civicrm_group_id );

		// Sanity check.
		if ( $wp_group_id === false ) {
			return;
		}

		// Loop through added contacts.
		if ( count( $contact_ids ) > 0 ) {
			foreach( $contact_ids AS $contact_id ) {

				// Get WordPress user ID.
				$user_id = $this->plugin->wordpress->user_id_get_by_contact_id( $contact_id );

				// Add user to "Groups" group.
				if ( $user_id !== false ) {
					$this->plugin->wordpress->group_member_add( $user_id, $wp_group_id );
				}

			}
		}

	}



	//##########################################################################



	/**
	 * Get a CiviCRM contact ID for a given WordPress user ID.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The numeric WordPress user ID.
	 * @return int|bool $contact_id The CiviCRM contact ID, or false on failure.
	 */
	public function contact_id_get_by_user_id( $user_id ) {

		// Bail if no CiviCRM.
		if ( ! $this->plugin->is_civicrm_initialised() ) {
			return false;
		}

		// Make sure CiviCRM file is included.
		require_once( 'CRM/Core/BAO/UFMatch.php' );

		// Search using CiviCRM's logic.
		$contact_id = CRM_Core_BAO_UFMatch::getContactId( $user_id );

		// Cast contact ID as boolean if we didn't get one.
		if ( empty( $contact_id ) ) {
			$contact_id = false;
		}

		/**
		 * Filter the result of the CiviCRM contact lookup.
		 *
		 * You can use this filter to create a CiviCRM contact if none is found.
		 * Return the new CiviCRM contact ID and the group linkage will be made.
		 *
		 * @since 0.1
		 *
		 * @param int|bool $contact_id The numeric ID of the CiviCRM contact, or false on failure.
		 * @param int $user_id The numeric ID of the WordPress user.
		 * @return int|bool $contact_id The numeric ID of the CiviCRM contact, or false on failure.
		 */
		$contact_id = apply_filters( 'civicrm_groups_sync_contact_id_get_by_user_id', $contact_id, $user_id );

		// --<
		return $contact_id;

	}



	/**
	 * Get a CiviCRM contact for a given WordPress user ID.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The numeric WordPress user ID.
	 * @return array|bool $contact The CiviCRM contact data, or false on failure.
	 */
	public function contact_get_by_user_id( $user_id ) {

		// Get the contact ID.
		$contact_id = $this->contact_id_get_by_user_id( $user_id );

		// Bail if we didn't get one.
		if ( empty( $contact_id ) ) {
			return false;
		}

		// Get domain org info.
		$contact = civicrm_api( 'contact', 'getsingle', array(
			'version' => 3,
			'id' => $contact_id,
		));

		// Bail if there's an error.
		if ( ! empty( $contact['is_error'] ) AND $contact['is_error'] == 1 ) {
			return false;
		}

		// --<
		return $contact;

	}



} // Class ends.
