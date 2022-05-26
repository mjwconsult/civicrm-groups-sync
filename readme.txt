=== CiviCRM Groups Sync ===
Contributors: needle, kcristiano
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, groups, sync
Requires at least: 4.9
Tested up to: 5.1
Stable tag: 0.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM Groups Sync keeps Contacts in CiviCRM Groups in sync with WordPress Users in groups provided by the Groups plugin.



== Description ==

CiviCRM Groups Sync keeps Contacts in CiviCRM Groups in sync with WordPress Users in groups provided by the [Groups plugin](https://wordpress.org/plugins/groups/).

### Requirements

This plugin requires a minimum of *WordPress 4.9*, *Groups 2.5* and *CiviCRM 5.8*.

### WordPress users

By default, this plugin does not create a WordPress user when a CiviCRM contact is added to a CiviCRM group which is synced to a Groups group. If you wish to do so, use a callback from the `civicrm_groups_sync_user_id_get_by_contact_id` filter to create a new WordPress user and return the user ID.

### CiviCRM contacts

By default, this plugin does not create a CiviCRM contact when a WordPress user is added to a Groups group which is synced to a CiviCRM group. If you wish to do so, use a callback from the `civicrm_groups_sync_contact_id_get_by_user_id` filter to create a new CiviCRM contact and return the contact ID.

### BuddyPress compatibility

If you are using both *BuddyPress* and *Groups* then you will also need [this plugin](https://github.com/itthinx/groups-buddypress-compatibility) in order to prevent conflicts between the two identically-named actions in these plugins.

### Permissions Sync

It may be useful to sync the capabilities/permissions using [CiviCRM Permissions Sync](https://develop.tadpole.cc/plugins/civicrm-permissions-sync)  Details are in the [README.md file](https://develop.tadpole.cc/plugins/civicrm-permissions-sync/-/blob/master/README.md)

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://develop.tadpole.cc/plugins/civicrm-groups-sync).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated and properly configured
1. Activate the plugin through the 'Plugins' menu in WordPress



== Changelog ==

= 0.1.1 =

Create two-way links between CiviCRM and WordPress groups


= 0.1 =

Initial release.
