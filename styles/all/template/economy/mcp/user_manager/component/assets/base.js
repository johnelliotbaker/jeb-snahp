var UserAssets = {};

UserAssets.save_user_balance = function(event)
{
    if (!UserSelection)
    {
        return false;
    }
    var user_id = UserSelection.data.user_id;
    if (user_id===undefined)
    {
        return false;
    }
    $user_balance = $('#user_balance');
    var balance = $user_balance.val();
    var url = '/app.php/snahp/economy/uam/set_user_balance/?u=' + user_id + '&b=' + balance;
    $('#user_balance_button').prop('disabled', true);
    $('#user_balance_spinner').css('display', '');
    $('#user_balance_text').text('Saving...');
    $.get(url).done((resp)=>{
        setTimeout(function() {
            $('#user_balance_spinner').css('display', 'none');
            $('#user_balance_text').text('Save');
            $('#user_balance_button').prop('disabled', false);
        }.bind(this), 1000);
    });
}
