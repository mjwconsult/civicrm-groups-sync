CiviCRM Groups Sync
===================

Please note: this is the development repository for *CiviCRM Groups Sync*.

*CiviCRM Groups Sync* is a WordPress plugin that keeps Contacts in CiviCRM Groups in sync with WordPress Users in groups provided by the [Groups plugin](https://wordpress.org/plugins/groups/).

#### Notes ####

This plugin requires a minimum of *WordPress 4.9*, *Groups 2.5* and *CiviCRM 5.8*.

##### WordPress users #####

By default, this plugin does not create a WordPress user when a CiviCRM contact is added to a CiviCRM group which is synced to a Groups group. If you wish to do so, use a callback from the `civicrm_groups_sync_user_id_get_by_contact_id` filter to create a new WordPress user and return the user ID.

##### CiviCRM contacts #####

By default, this plugin does not create a CiviCRM contact when a WordPress user is added to a Groups group which is synced to a CiviCRM group. If you wish to do so, use a callback from the `civicrm_groups_sync_contact_id_get_by_user_id` filter to create a new CiviCRM contact and return the contact ID.

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM Groups Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-groups-sync`
2. Activate the plugin (in multisite, network activate)
3. You are done!

###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
