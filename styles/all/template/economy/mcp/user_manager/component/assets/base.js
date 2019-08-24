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
    $.get(url).done((resp)=>{
        // location.reload();
    });
}
