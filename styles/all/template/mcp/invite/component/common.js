// function url_clear_user_id()
// {
//     // https://stackoverflow.com/questions/7126563/jquery-remove-one-url-variable
//     location.href=location.href.replace(/&?user_id=([^&]$|[^&]*)/i, "");
// }
//
// function url_set_user_id(user_id)
// {
//     var regex = /&?user_id=([^&]$|[^&]*)/i;
//     user_id = parseInt(user_id);
//     if (regex.exec(location.href))
//     {
//         location.href = location.href.replace(/(&?user_id)=([^&]$|[^&]*)/i, '$1=' + user_id);
//     }
//     else
//     {
//         location.href = location.href + '&user_id=' + user_id;
//     }
// }
