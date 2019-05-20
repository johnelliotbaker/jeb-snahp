function check_all_topics(b_check=true)
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
        createCookie(cookie_prefix + 'mcp_move_per_page', '0:' + per_page + ';', 365);
        location.reload();
    });
    // Source forum id
    $forum_selector = $('#from_forum_id');
    $forum_selector.change(()=>{
        var mcp_move_from_fid = $forum_selector.val();
        console.log(mcp_move_from_fid);
        createCookie(cookie_prefix + 'mcp_move_from_fid', '0:' + mcp_move_from_fid + ';', 365);
        location.reload();
    })
    // Is request checkbox
    $is_request_checkbox = $('#is_request_checkbox');
    $is_request_checkbox.change(()=>{
        var b_request = $is_request_checkbox.prop('checked') ? 1 : 0;
        createCookie(cookie_prefix + 'mcp_move_b_request', '0:' + b_request + ';', 365);
        location.reload();
    })
    // Request type
    $request_type_selector = $('#mcp_move_request_type_dropdown');
    $request_type_selector.change(()=>{
        var request_type = $request_type_selector.val();
        createCookie(cookie_prefix + 'mcp_move_request_type', '0:' + request_type + ';', 365);
        location.reload();
    });
});
