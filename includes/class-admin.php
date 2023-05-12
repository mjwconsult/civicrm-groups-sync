<?php
/**
 * Admin class.
 *
 * Handles admin functionality.
 *
 * @package CiviCRM_Groups_Sync
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * A class that encapsulates admin functionality.
 *
 * @since 0.1
 */
class CiviCRM_Groups_Sync_Admin {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Plugin version.
	 *
	 * @since 0.1
	 * @access public
	 * @var string $plugin_version The plugin version.
	 */
	public $plugin_version;

	/**
	 * Plugin settings.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $settings The plugin settings.
	 */
	public $settings = [];

	/**
	 * Parent Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string $parent_page The parent page.
	 */
	public $parent_page;

	/**
	 * Settings Page.
	 *
	 * @since 0.1
	 * @access public
	 * @var string $settings_page The settings page.
	 */
	public $settings_page;

	/**
	 * Class constructor.
	 *
	 * @since 0.1
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Add action for init.
		add_action( 'civicrm_groups_sync_loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Load plugin version.
		$this->plugin_version = $this->option_get( 'civicrm_groups_sync_version', 'none' );

		// Perform any upgrade tasks.
		$this->upgrade_tasks();

		// Upgrade version if needed.
		if ( CIVICRM_GROUPS_SYNC_VERSION !== $this->plugin_version ) {
			$this->store_version();
		}

		// Load settings array.
		$this->settings = $this->option_get( 'civicrm_groups_sync_settings', $this->settings );

		// Upgrade settings.
		$this->upgrade_settings();

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Perform upgrade tasks.
	 *
	 * @since 0.1
	 */
	public function upgrade_tasks() {

		/*
		// For upgrades by version, use something like the following.
		if ( version_compare( CIVICRM_GROUPS_SYNC_VERSION, '0.3.4', '>=' ) ) {
			// Do something
		}
		*/

	}

	/**
	 * Upgrade settings when required.
	 *
	 * @since 0.1.2
	 */
	public function upgrade_settings() {

		// Don't save by default.
		$save = false;

		/*
		// Some setting may not exist.
		if ( ! $this->setting_exists( 'some_setting' ) ) {

			// Add it from defaults.
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'some_setting', $settings['some_setting'] );
			$save = true;

		}
		*/

		// Save settings if need be.
		if ( true === $save ) {
			$this->settings_save();
		}

	}

	/**
	 * Store the plugin version.
	 *
	 * @since 0.1
	 */
	public function store_version() {

		// Store version.
		$this->option_set( 'civicrm_groups_sync_version', CIVICRM_GROUPS_SYNC_VERSION );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Add admin page to Settings menu.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_action( 'admin_menu', array( $this, 'admin_menu' ) );

	}

	// -------------------------------------------------------------------------

	/**
	 * Add admin menu items for this plugin.
	 *
	 * @since 0.1
	 */
	public function admin_menu() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.1
		 *
		 * @param string The default capability for access to settings page.
		 */
		$capability = apply_filters( 'civicrm_groups_sync_page_settings_cap', 'manage_options' );

		// Check User permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Add the admin page to the Settings menu.
		$this->parent_page = add_options_page(
			__( 'CiviCRM Groups Sync: Settings', 'civicrm-groups-sync' ),
			__( 'CiviCRM Groups Sync', 'civicrm-groups-sync' ),
			$capability,
			'civicrm_groups_sync_parent',
			[ $this, 'page_settings' ]
		);

		// Add help text.
		add_action( 'admin_head-' . $this->parent_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_action( 'admin_print_styles-' . $this->parent_page, array( $this, 'admin_css' ) );

		// Add settings page.
		$this->settings_page = add_submenu_page(
			'civicrm_groups_sync_parent', // Parent slug.
			__( 'CiviCRM Groups Sync: Settings', 'civicrm-groups-sync' ), // Page title.
			__( 'Settings', 'civicrm-groups-sync' ), // Menu title.
			$capability, // Required caps.
			'civicrm_groups_sync_settings', // Slug name.
			[ $this, 'page_settings' ] // Callback.
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Add help text.
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_action( 'admin_print_styles-' . $this->settings_page, array( $this, 'admin_css' ) );

	}

	/**
	 * Highlight the plugin's parent menu item.
	 *
	 * Regardless of the actual admin screen we are on, we need the parent menu
	 * item to be highlighted so that the appropriate menu is open by default
	 * when the subpage is viewed.
	 *
	 * @since 0.1
	 *
	 * @global string $plugin_page The current plugin page.
	 * @global string $submenu_file The current submenu.
	 */
	public function admin_menu_highlight() {

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = [
			'civicrm_groups_sync_settings',
		];

		/**
		 * Filter the list of subpages.
		 *
		 * @since 0.1
		 *
		 * @param array $subpages The existing list of subpages.
		 */
		$subpages = apply_filters( 'civicrm_groups_sync_subpages', $subpages );

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$plugin_page = 'civicrm_groups_sync_parent';
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu_file = 'civicrm_groups_sync_parent';
		}

	}

	/**
	 * Initialise plugin help.
	 *
	 * @since 0.1
	 */
	public function admin_head() {

		// Get screen object.
		$screen = get_current_screen();

		// Pass to method in this class.
		$this->admin_help( $screen );

	}

	/**
	 * Adds help copy to admin page.
	 *
	 * @since 0.1
	 *
	 * @param object $screen The existing WordPress screen object.
	 * @return object $screen The amended WordPress screen object.
	 */
	public function admin_help( $screen ) {

		// Init page IDs.
		$pages = [
			$this->parent_page,
			$this->settings_page,
		];

		// Kick out if not our screen.
		if ( ! in_array( $screen->id, $pages ) ) {
			return $screen;
		}

		// Add a tab - we can add more later.
		$screen->add_help_tab( [
			'id'      => 'civicrm_groups_sync',
			'title'   => __( 'CiviCRM Groups Sync', 'civicrm-groups-sync' ),
			'content' => $this->admin_help_get(),
		] );

		// --<
		return $screen;

	}

	/**
	 * Get help text.
	 *
	 * @since 0.1
	 *
	 * @return string $help The help text formatted as HTML.
	 */
	public function admin_help_get() {

		// Start buffering.
		ob_start();

		// Include template.
		include CIVICRM_GROUPS_SYNC_PATH . 'assets/templates/help/settings-help.php';

		// Save the output and flush the buffer.
		$help = ob_get_clean();

		// --<
		return $help;

	}

	// -------------------------------------------------------------------------

	/**
	 * Show our settings page.
	 *
	 * @since 0.1
	 */
	public function page_settings() {

		/**
		 * Set capability but allow overrides.
		 *
		 * @since 0.1
		 *
		 * @param string The default capability for access to settings page.
		 */
		$capability = apply_filters( 'civicrm_groups_sync_page_settings_cap', 'manage_options' );

		// Check User permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Get admin page URLs.
		$urls = $this->page_get_urls();

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 0.1
		 *
		 * @param bool False by default - do not show tabs.
		 */
		$show_tabs = apply_filters( 'civicrm_groups_sync_show_tabs', false );

		// Include template.
		include CIVICRM_GROUPS_SYNC_PATH . 'assets/templates/admin/settings-page.php';

	}

	/**
	 * Get admin page URLs.
	 *
	 * @since 0.1
	 *
	 * @return array $urls The array of admin page URLs.
	 */
	public function page_get_urls() {

		// Only calculate once.
		if ( isset( $this->urls ) ) {
			return $this->urls;
		}

		// Init return.
		$this->urls = [];

		// Get admin page URLs.
		$this->urls['settings'] = menu_page_url( 'civicrm_groups_sync_settings', false );

		/**
		 * Filter the list of URLs.
		 *
		 * @since 0.1
		 *
		 * @param array $urls The existing list of URLs.
		 */
		$this->urls = apply_filters( 'civicrm_groups_sync_page_urls', $this->urls );

		// --<
		return $this->urls;

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.1
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_submit_url_get() {

		// Sanitise admin page URL.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$target_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! empty( $target_url ) ) {
			$url_array = explode( '&', $target_url );
			if ( ! empty( $url_array ) ) {
				$url_raw = str_replace( '&amp;updated=true', '', $url_array[0] );
				$target_url = htmlentities( $url_raw . '&updated=true' );
			}
		}

		// --<
		return $target_url;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get default settings for this plugin.
	 *
	 * @since 0.1.2
	 *
	 * @return array $settings The default settings for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = [];

		/**
		 * Filter default settings.
		 *
		 * @since 0.1.2
		 *
		 * @param array $settings The array of default settings.
		 */
		$settings = apply_filters( 'civicrm_groups_sync_settings_default', $settings );

		// --<
		return $settings;

	}

	/**
	 * Save array as option.
	 *
	 * @since 0.1
	 *
	 * @return bool Success or failure.
	 */
	public function settings_save() {

		// Save array as option.
		return $this->option_set( 'civicrm_groups_sync_settings', $this->settings );

	}

	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name = '' ) {

		// Get existence of setting in array.
		return array_key_exists( $setting_name, $this->settings );

	}

	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed  $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[ $setting_name ] : $default;

	}

	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed  $value The value of the setting.
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// Set setting.
		$this->settings[ $setting_name ] = $value;

	}

	/**
	 * Deletes a specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name = '' ) {

		// Unset setting.
		unset( $this->settings[ $setting_name ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Test existence of a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name = '' ) {

		// Test by getting option with unlikely default.
		if ( $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @param string $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name = '', $default = false ) {

		// Get option.
		$value = get_option( $option_name, $default );

		// --<
		return $value;

	}

	/**
	 * Set a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// Update option.
		return update_option( $option_name, $value );

	}

	/**
	 * Delete a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $success True if the option was successfully deleted.
	 */
	public function option_delete( $option_name = '' ) {

		// Delete option.
		return delete_option( $option_name );

	}

}
