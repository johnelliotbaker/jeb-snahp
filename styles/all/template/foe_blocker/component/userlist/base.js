var Foe_blocker = {};

Foe_blocker.transfer = function(i_row)
{
    var data = this.collect_row(i_row);
    this.populate_form(data);
}

Foe_blocker.populate_form = function(data)
{
    console.log(data['allow_viewtopic']);
    $('input[name="post_id"]').val(data['post_id']);
    $('input[name="emergency_blocked_id"]').val(data['blocked_id']);
    $('input[name="block_reason"]').val(data['block_reason']);
    $('#radio_allow_viewtopic_1').prop('checked', data['allow_viewtopic']);
    $('#radio_allow_viewtopic_2').prop('checked', !data['allow_viewtopic']);
    $('#radio_allow_reply_1').prop('checked', data['allow_reply']);
    $('#radio_allow_reply_2').prop('checked', !data['allow_reply']);
    $('#radio_allow_pm_1').prop('checked', data['allow_pm']);
    $('#radio_allow_pm_2').prop('checked', !data['allow_pm']);
}

Foe_blocker.collect_row = function(i_row)
{
    var block_reason = $('#block_reason_' + i_row).text();
    var blocked_id = $('#blocked_id_' + i_row).val();
    var post_id = $('#post_id_' + i_row).val();
    var allow_viewtopic = $('#allow_viewtopic_' + i_row).data('value')
    var allow_reply = $('#allow_reply_' + i_row).data('value');
    var allow_pm = $('#allow_pm_' + i_row).data('value');
    var emergency_blocked_id = $('#emergency_blocked_id_' + i_row).data('value');
    var data = {
        'post_id': post_id,
        'blocked_id': blocked_id,
        'block_reason': block_reason,
        'allow_viewtopic': allow_viewtopic,
        'allow_reply': allow_reply,
        'allow_pm': allow_pm,
    };
    return data;
}
