function show_invite(resp)
{
    var code = resp['keyphrase'];
    $content = $('#new_invitation_code');
    $content.empty();
    $content.append($('<h4>' + code + '</h4>'));
}

function generate_invite()
{
    // var inviter_id = parseInt($('#username_searchbox').val());
    // if (!inviter_id) return false;
    var inviter_id = '';
    var url = '/app.php/snahp/invite/generate_invite_json/?uid=' + inviter_id;
    $.get(url).done((resp)=>{
        show_invite(resp);
    });
}


$(function () {
    $('#generate_btn').click((e)=>{
        generate_invite();
    });
});
