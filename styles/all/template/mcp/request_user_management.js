var req_user_row_template = `
<table>
  <thead>
    <tr style="border-top:1px solid black;">
      <th>UID</th>
      <th>Used (0)</th>
      <th>Cycle (0)</th>
      <th>Bonus (1)</th>
      <th>Next Reset Time</th>
      <th>Status</th>
      <th>Cycle Override (-1)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><input style="width:98%;" type="text" name="user_id" id="user_id" value="{USER_ID}"></td>
      <td><input style="width:98%;" type="text" name="n_use" id="n_use" value="{N_USE}"></td>
      <td><input style="width:98%;" type="text" name="n_use_this_cycle" id="n_use_this_cycle" value="{N_USE_THIS_CYCLE}"></td>
      <td><input style="width:98%;" type="text" name="n_offset" id="n_offset" value="{N_OFFSET}"></td>
      <td><input style="width:98%;" type="text" name="reset_time" id="reset_time" value="{RESET_TIME}"></td>
      <td><input style="width:98%;" type="text" name="status" id="status" value="{STATUS}"></td>
      <td><input style="width:98%;" type="text" name="n_use_per_cycle_override" id="n_use_per_cycle_override" value="{STATUS}"></td>
    </tr>
  </tbody>
</table>`


function get_username()
{
  var username = $("#usernames").val();
  return username;
}

function show_user_requests()
{
  var username = get_username();
  if (!username)
  {
    $('#info').text('Username cannot be empty.');
    return false;
  }
  $.get(`/app.php/snahp/reqs/myrequests/` + username + `/`)
    .done((resp) => {
    populate_request_details(resp);
  })
}

function populate_request_details(resp)
{
    $table = $("#mcp_req_user_details");
    $table.empty();
    $('.ui-button').css('outline', 'none');
    var count = 1;
    for (var entry of resp)
    {
      var title = entry.ti;
      if (title)
      {
        var index = '<td>' + count++ + '</td>';
        var url = '<td><b><a target="_blank" href="/viewtopic.php?t=' + entry.t + '">' + title + '</a></b></td>';
        var datetime = '<td>' + entry.d + '</td>';
        var status = '<td>' + entry.s + '</td>';
        var row = '<tr>' + index + url + datetime + status + '</tr>';
        $table.append($(row));
      }
    }
    if (!resp)
    {
        var index = '<td>No Requests</td>';
        var row = '<tr>' + index + '</tr>';
        $table.append($(row));
    }
}

function reset_user()
{
  $('#request_stat_content').empty();
  var username = get_username();
  if (!username)
  {
    $('#info').text('Username cannot be empty.');
    return false;
  }
  $.get(`/app.php/snahp/acp_reqs_reset_user/` + username + `/`)
  .done((resp) => {
    $('#info').text('Status: ' + resp['status']);
    populate_request_user();
    show_user_requests();
  })
  .fail(()=>
  {
    $('#info').text('Error: User not found in request user database.');
  });
}

function populate_request_user()
{
  $('#request_stat_content').empty();
  var username = get_username();
  if (!username)
  {
    $('#info').text('Username cannot be empty.');
    return;
  }
  $.get(`/app.php/snahp/acp_reqs_get_userinfo/` + username + `/`)
    .done((resp) => {
      $('#info').text('');
      var user_id = resp.user_id;
      if (!user_id)
      {
        $('#info').text('Error: User not found in request user database.');
        return;
      }
      $(req_user_row_template).appendTo($('#request_stat_content'));
      var n_use = resp.n_use;
      var n_offset = resp.n_offset;
      var reset_time = resp.reset_time;
      var status = resp.status;
      var n_use_per_cycle_override = resp.n_use_per_cycle_override;
      for (var field in resp)
      {
        $('#'+field).val(resp[field]);
      }
    })
    .fail(()=>
      {
        $('#info').text('Enter Valid Username.');
        $('#request_stat_content').empty();
      });
}

function refresh() 
{
    populate_request_user();
    show_user_requests();
}

$(document).ready(function()
{
  populate_request_user();
  $("#usernames").on('keydown', (e) => {
  if (e.which == 13) {
    e.preventDefault();
    populate_request_user();
    show_user_requests();
  }
  });
});
