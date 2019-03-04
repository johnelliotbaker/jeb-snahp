function list_invites(resp)
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
        html += '</tr>';
        $entry = $(html).appendTo($content);
    }
}

$(function () {
    $list = $('#list_btn').click((e)=>{
        var inviter_id = parseInt($('#inviter_id_searchbox').val());
        if (!inviter_id) return false;
        var url = '/app.php/snahp/invite/list_json/?uid=' + inviter_id;
        $.get(url).done((resp)=>{
            list_invites(resp);
        });
    });
});
