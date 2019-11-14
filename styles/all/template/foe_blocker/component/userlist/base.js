var Foe_blocker = {};

Foe_blocker.transfer = function(i_row)
{
    var data = this.collect_row(i_row);
    this.populate_form(data);
}

Foe_blocker.populate_form = function(data)
{
    if (data['post_id']>0)
    {
        $('input[name="post_id"]').val(data['post_id']);
    }
    else
    {
        $('input[name="post_id"]').val('');
    }
    $('input[name="triage_username"]').val(data['triage_username']);
    $('input[name="block_reason"]').val(data['block_reason']);
    $('#radio_allow_viewtopic_1').prop('checked', data['allow_viewtopic']);
    $('#radio_allow_viewtopic_2').prop('checked', !data['allow_viewtopic']);
    $('#radio_allow_reply_1').prop('checked', data['allow_reply']);
    $('#radio_allow_reply_2').prop('checked', !data['allow_reply']);
    $('#radio_allow_pm_1').prop('checked', data['allow_pm']);
    $('#radio_allow_pm_2').prop('checked', !data['allow_pm']);
    if (data.triage_mode)
    {
        User_blocker_form.select_mode('triage');
    }
    else
    {
        User_blocker_form.select_mode('normal');
    }

}

Foe_blocker.collect_row = function(i_row)
{
    var block_reason = $('#block_reason_' + i_row).text();
    var blocked_id = $('#blocked_id_' + i_row).val();
    var triage_username = $('#triage_username_' + i_row).text();
    var post_id = $('#post_id_' + i_row).val();
    var allow_viewtopic = $('#allow_viewtopic_' + i_row).data('value')
    var allow_reply = $('#allow_reply_' + i_row).data('value');
    var allow_pm = $('#allow_pm_' + i_row).data('value');
    var triage_mode = $('#status_' + i_row).data('triage');
    var data = {
        'post_id': post_id,
        'blocked_id': blocked_id,
        'triage_username': triage_username,
        'block_reason': block_reason,
        'allow_viewtopic': allow_viewtopic,
        'allow_reply': allow_reply,
        'allow_pm': allow_pm,
        'triage_mode': triage_mode,
    };
    return data;
}
