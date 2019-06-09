$(function () {
    var tid = {S_TOPIC_ID};
    $to_forum_id = $("#to_forum_id");
    var fid = $to_forum_id.children("option:selected").val();
    $to_forum_id.change(()=>{
        fid = $to_forum_id.children("option:selected").val();
        // Remember selected destination
        // Used by insert_move_topic_button in mod_listner.php
        Cookie.set('mcp', 'move_topic.dest', fid);
    });
    $('#mod_move_topic').click(()=>{
        var url = '/app.php/snahp/admin/move_topic/' + tid + '/' + fid + '/';
        $.get(url, (resp)=>{
            if (resp['status'] == 'SUCCESS')
            {
                location.reload();
            }
            else
            {
                alert('Something went wrong. Could not move the topic.');
            }
        });
    })

});
