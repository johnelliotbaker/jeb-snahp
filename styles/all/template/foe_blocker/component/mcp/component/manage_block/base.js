var User_block_mcp_manage_block = {};

User_block_mcp_manage_block.get_row_data = function($elem)
{
    try {
        return $elem.parent().data().data;
    } catch (e) {
        return {};
    }
}

User_block_mcp_manage_block.save_mod_reason = function(event)
{
    var mod_reason = $('#mod_reason').val();
    var url = '/app.php/snahp/block_mcp/save_mod_reason/';
    var data = {
        'blocked_id': $('#blocked_id').val(),
        'blocker_id': $('#blocker_id').val(),
        'mod_reason': mod_reason
    };
    $.post(url, data).done((resp)=>{
        if (resp.hasOwnProperty('status') && resp.status=='1') { location.reload(); }
        else { alert('Error. ' + resp.reason); }
    });
}

User_block_mcp_manage_block.populate_mod_reason_form = function(event)
{
    var row = this.get_row_data($(event));
    var blocker_text = `${row.blocker_username}`;
    $('#blocker_username').text(blocker_text).css('color', '#' + row.blocker_user_colour);
    var blocked_text = `${row.blocked_username}`;
    $('#blocked_username').text(blocked_text).css('color', '#' + row.blocked_user_colour);
    $('#blocked_id').val(row.blocked_id);
    $('#blocker_id').val(row.blocker_id);
    $('#mod_reason').val(row.mod_reason);
}

User_block_mcp_manage_block.toggle_freeze = function(event)
{
    var row = this.get_row_data($(event));
    var permission_type = $(event).data().permission_type;
    var url = `/app.php/snahp/block_mcp/toggle_freeze/?blocked_id=${row.blocked_id}&blocker_id=${row.blocker_id}`;
    $.get(url).done((resp)=>{
        if (resp.hasOwnProperty('status') && resp.status=='1') { location.reload(); }
        else { alert('Error. ' + resp.reason); }
    });
}

User_block_mcp_manage_block.toggle_permission = function(event)
{
    var row = this.get_row_data($(event));
    var permission_type = $(event).data().permission_type;
    var url = `/app.php/snahp/block_mcp/toggle_permission/?blocked_id=${row.blocked_id}&blocker_id=${row.blocker_id}&permission_type=${permission_type}`;
    $.get(url).done((resp)=>{
        if (resp.hasOwnProperty('status') && resp.status=='1') { location.reload(); }
        else { alert('Error. ' + resp.reason); }
    });
}

User_block_mcp_manage_block.toggle_perma_block = function(event)
{
    var row = this.get_row_data($(event));
    var b_permanent = row.b_permanent;
    var url = `/app.php/snahp/block_mcp/toggle_perma_block/?blocked_id=${row.blocked_id}&blocker_id=${row.blocker_id}`;
    $.get(url).done((resp)=>{
        if (resp.hasOwnProperty('status') && resp.status=='1') { location.reload(); }
        else { alert('Error. ' + resp.reason); }
    });
}
