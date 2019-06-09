var Mcp_mass_move = {};

Mcp_mass_move.check_all_topics = function(b_check=true)
{
    $checkboxes = $("input[name^='tid_']");
    for (var i = 0, len = $checkboxes.length; i < len; i++) {
        if (b_check==true)
        {
            $($checkboxes[i]).prop('checked', true);
        }
        else
        {
            $($checkboxes[i]).prop('checked', false);
        }
    }
}


$(function () {
    // per page control
    $perpage_dropdown = $('#mcp_move_per_page_dropdown');
    var cookie_prefix = $('input[name="snp_cookie_prefix"]').val();
    $perpage_dropdown.change(()=>{
        var per_page = $perpage_dropdown.val();
        Cookie.set('mcp', 'mass_move.per_page', per_page);
        location.reload();
    });
    // Source forum id
    $forum_selector = $('#from_forum_id');
    $forum_selector.change(()=>{
        var mcp_move_from_fid = $forum_selector.val();
        Cookie.set('mcp', 'mass_move.from_fid', mcp_move_from_fid);
        location.reload();
    })
    // Is request checkbox
    $is_request_checkbox = $('#is_request_checkbox');
    $is_request_checkbox.change(()=>{
        var b_request = $is_request_checkbox.prop('checked') ? 1 : 0;
        Cookie.set('mcp', 'mass_move.b_request', b_request);
        location.reload();
    })
    // Request type
    $request_type_selector = $('#mcp_move_request_type_dropdown');
    $request_type_selector.change(()=>{
        var request_type = $request_type_selector.val();
        Cookie.set('mcp', 'mass_move.request_type', request_type);
        location.reload();
    });
});
