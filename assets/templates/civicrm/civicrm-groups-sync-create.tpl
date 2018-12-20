{* template block that contains the new field *}
<table>
  <tr class="civicrm_groups_sync_create_block">
    <td class="label"><label for="civicrm_groups_sync_create">{ts}Create Synced Group{/ts}</label></td>
    <td>{$form.civicrm_groups_sync_create.html} <span class="description">{ts}If you are creating a Synced Group, you only need to fill out the "Title" field (and optionally the "Description" field) above. The Group Type will be set to "Access Control" automatically.{/ts}</span></td>
  </tr>
</table>

{* reposition the above block after #someOtherBlock *}
<script type="text/javascript">
  // jQuery will not move an item unless it is wrapped
  cj('tr.civicrm_groups_sync_create_block').insertBefore('.crm-group-form-block .crm-group-form-block-group_type');
</script>
