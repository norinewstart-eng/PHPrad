<?php
$current_page = $this->set_current_page_link();
$csrf_token = Csrf::$token;
$field_name = $this->route->field_name;
$field_value = $this->route->field_value;
$view_data = $this->view_data;
$records = $view_data->records;
$record_count = $view_data->record_count;
$total_records = $view_data->total_records;
if (!empty($records)) {
?>
<!--record-->
<?php
$counter = 0;
foreach($records as $data){
$rec_id = (!empty($data['id']) ? urlencode($data['id']) : null);
$counter++;
?>
<tr>
    <th class=" td-checkbox">
        <label class="custom-control custom-checkbox custom-control-inline">
            <input class="optioncheck custom-control-input" name="optioncheck[]" value="<?php echo $data['id'] ?>" type="checkbox" />
                <span class="custom-control-label"></span>
            </label>
        </th>
        <th class="td-sno"><?php echo $counter; ?></th>
        <td class="td-id"><a href="<?php print_link("warehouses/view/$data[id]") ?>"><?php echo $data['id']; ?></a></td>
        <td class="td-name">
            <span  data-value="<?php echo $data['name']; ?>" 
                data-pk="<?php echo $data['id'] ?>" 
                data-url="<?php print_link("warehouses/editfield/" . urlencode($data['id'])); ?>" 
                data-name="name" 
                data-title="Enter Name" 
                data-placement="left" 
                data-toggle="click" 
                data-type="text" 
                data-mode="popover" 
                data-showbuttons="left" 
                class="is-editable" >
                <?php echo $data['name']; ?> 
            </span>
        </td>
        <td class="td-code">
            <span  data-value="<?php echo $data['code']; ?>" 
                data-pk="<?php echo $data['id'] ?>" 
                data-url="<?php print_link("warehouses/editfield/" . urlencode($data['id'])); ?>" 
                data-name="code" 
                data-title="Enter Code" 
                data-placement="left" 
                data-toggle="click" 
                data-type="text" 
                data-mode="popover" 
                data-showbuttons="left" 
                class="is-editable" >
                <?php echo $data['code']; ?> 
            </span>
        </td>
        <td class="td-location">
            <span  data-pk="<?php echo $data['id'] ?>" 
                data-url="<?php print_link("warehouses/editfield/" . urlencode($data['id'])); ?>" 
                data-name="location" 
                data-title="Enter Location" 
                data-placement="left" 
                data-toggle="click" 
                data-type="textarea" 
                data-mode="popover" 
                data-showbuttons="left" 
                class="is-editable" >
                <?php echo $data['location']; ?> 
            </span>
        </td>
        <td class="td-project_id">
            <span  data-value="<?php echo $data['project_id']; ?>" 
                data-pk="<?php echo $data['id'] ?>" 
                data-url="<?php print_link("warehouses/editfield/" . urlencode($data['id'])); ?>" 
                data-name="project_id" 
                data-title="Enter Project Id" 
                data-placement="left" 
                data-toggle="click" 
                data-type="number" 
                data-mode="popover" 
                data-showbuttons="left" 
                class="is-editable" >
                <?php echo $data['project_id']; ?> 
            </span>
        </td>
        <th class="td-btn">
            <a class="btn btn-sm btn-success has-tooltip" title="<?php print_lang('view_record'); ?>" href="<?php print_link("warehouses/view/$rec_id"); ?>">
                <i class="fa fa-eye"></i> <?php print_lang('view'); ?>
            </a>
            <a class="btn btn-sm btn-info has-tooltip" title="<?php print_lang('edit_this_record'); ?>" href="<?php print_link("warehouses/edit/$rec_id"); ?>">
                <i class="fa fa-edit"></i> <?php print_lang('edit'); ?>
            </a>
            <a class="btn btn-sm btn-danger has-tooltip record-delete-btn" title="<?php print_lang('delete_this_record'); ?>" href="<?php print_link("warehouses/delete/$rec_id/?csrf_token=$csrf_token&redirect=$current_page"); ?>" data-prompt-msg="Are you sure you want to delete this record?" data-display-style="modal">
                <i class="fa fa-times"></i>
                <?php print_lang('delete'); ?>
            </a>
        </th>
    </tr>
    <?php 
    }
    ?>
    <?php
    } else {
    ?>
    <td class="no-record-found col-12" colspan="100">
        <h4 class="text-muted text-center ">
            <?php print_lang('no_record_found'); ?>
        </h4>
    </td>
    <?php
    }
    ?>
    