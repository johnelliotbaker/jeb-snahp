function disable_invite(id, inviter_id)
{
    var url = '/app.php/snahp/invite/disable_invite/?iid=' + id;
    $.get(url).done((resp)=>{
        populate_invite_list(inviter_id);
    })
}

function list_invites(resp, username)
{
    $content = $('#invite_content').empty();
    for(var entry of resp)
    {
        var html = '<tr>';
        html += `<td class="text-center">${entry['id']}</td>`;
        html += `<td class="text-center">${entry['inviter_id']}</td>`;
        html += `<td class="text-center">${entry['keyphrase']}</td>`;
        html += `<td class="text-center"><b>${entry['redeemer']}</b></td>`;
        html += `<td class="text-center">${entry['create_time']}</td>`;
        html += `<td class="text-center">${entry['redeem_time']}</td>`;
        html += `<td class="text-center">${entry['status_strn']}</td>`;
        html += '<td onclick="disable_invite('  + entry.id + ', \'' + username + '\');" class="text-center"><button type="button" class="btn btn-danger" style="padding:1px 4px 1px 4px; font-size:8px">X</button></td>';
        html += '</tr>';
        $entry = $(html).appendTo($content);
    }
}

function populate_invite_list(username)
{
        if (!username) return false;
        var url = '/app.php/snahp/invite/list_json/?u=' + username;
        $.get(url).done((resp)=>{
            list_invites(resp, username);
        });
}

$(function () {
    $list = $('#list_btn').click((e)=>{
        var username = $('#username_searchbox').val();
        $content = $('#invite_content').empty();
        populate_invite_list(username);
    });
});
