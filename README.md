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

##### BuddyPress compatibility #####

If you are using both *BuddyPress* and *Groups* then you will also need [this plugin](https://github.com/itthinx/groups-buddypress-compatibility) in order to prevent conflicts between the two identically-named actions in these plugins.

### Permissions Sync

It may be useful to sync the capabilities/permissions using [CiviCRM Permissions Sync](https://develop.tadpole.cc/plugins/civicrm-permissions-sync)  Details are in the [README.md file](https://develop.tadpole.cc/plugins/civicrm-permissions-sync/-/blob/master/README.md)

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM Groups Sync* as a ZIP file from the git repository, do the following to install and activate the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-groups-sync`
2. Activate the plugin (in multisite, network activate)
3. You are done!

###### git clone ######

If you have cloned the code from the git repository, it is assumed that you know what you're doing.

#### Adding Groups ####

##### WordPress Groups #####

When you add a group in WordPress, you'll be able to select an option that also creates a group in CiviCRM. This allows you to update a User in a WordPress group and it will update that contact in CiviCRM and add them to the CiviCRM group.

![CGS add group in WordPress](https://develop.tadpole.cc/danaskallman/civicrm-groups-sync/raw/docs/docs/images/cgs-wp-add-group.png)

With the groups in WordPress, you'll be also be able to assign capabilities or ACLs so that users in that groups can access the parts of CiviCRM they can work on.

![CGS for capability/ACL management](https://develop.tadpole.cc/danaskallman/civicrm-groups-sync/raw/docs/docs/images/cgs-capabilities-mgmt.png)

Tip: Add users as WordPress using the default user roles, like Subscriber or Author. Then add them to the appropriate group to get the ACLs needed to access CiviCRM.

##### CiviCRM Groups #####

When adding a group in CiviCRM, there will be an option to also create a group in WordPress. These groups are all Access Control groups, since capabilities can be assigned to the groups, as referenced above.

![CGS add group in CiviCRM](https://develop.tadpole.cc/danaskallman/civicrm-groups-sync/raw/docs/docs/images/cgs-add-group-civicrm.png)
