{* template block that contains the new field *}
<table>
  <tr class="civicrm_groups_sync_edit_block">
    <td class="label"><label for="civicrm_groups_sync_edit">{ts}Existing Synced Group{/ts}</label></td>
    <td>{$form.civicrm_groups_sync_edit.html} <span class="description">{ts}This group is a Synced Group and already has an associated group in WordPress{/ts}: <a href="{$civicrm_groups_sync_edit_url}">{ts}Group Settings{/ts}</a></span></td>
  </tr>
</table>

{* reposition the above block after #someOtherBlock *}
<script type="text/javascript">
  // jQuery will not move an item unless it is wrapped
  cj('tr.civicrm_groups_sync_edit_block').insertBefore('.crm-group-form-block .crm-group-form-block-group_type');
</script>
