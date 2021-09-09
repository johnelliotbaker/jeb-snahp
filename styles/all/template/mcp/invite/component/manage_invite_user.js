function contextual_display_buttons() {
  $iid = $("#invitation_id");
  $searchbox = $("#username_searchbox");
  $create_btn = $("#create_invite_user_btn");
  $modify_btn = $("#modify_invite_user_btn");
  var username = $("#username_searchbox").val();
  if ($iid.val() == -1 || !username) {
    hide($modify_btn);
    hide($create_btn);
  } else if ($iid.val() > 0) {
    show($modify_btn);
    hide($create_btn);
  } else {
    hide($modify_btn);
    show($create_btn);
  }
}

function clear_manager() {
  var field_names = [
    "invitation_id",
    "user_id_display",
    "user_id",
    "current_available",
    "banned",
    "public_ban_message",
    "internal_ban_message",
  ];
  for (var fn of field_names) {
    $("#" + fn).empty();
    $("#" + fn).val("");
  }
}

function create_invite_user() {
  var username = $("#username_searchbox").val();
  var url = "/app.php/snahp/invite/insert_invite_user/?u=" + username;
  $.get(url).done((resp) => {});
  setTimeout(
    function () {
      $list_btn = $("#list_btn").click();
    }.bind(this),
    10
  );
}

function populate_manager(resp) {
  if ("invitation_id" in resp) {
    for (var key in resp) {
      $elem = $("#" + key);
      var val = resp[key];
      $elem.text(val);
      $elem.val(val);
      if (key == "user_id") {
        $("#user_id_display").text(val);
      }
    }
  } else {
    clear_manager();
  }
}

$(function () {
  $create_btn = $("#create_invite_user_btn");
  $modify_btn = $("#modify_invite_user_btn");
  hide($modify_btn);
  hide($create_btn);
  $iid = $("#invitation_id");
  $iid.change(() => {
    contextual_display_buttons();
  });
  $create_btn.click((e) => {
    create_invite_user();
  });
  $list = $("#list_btn").click((e) => {
    clear_manager();
    var username = $("#username_searchbox").val();
    if (!username) {
      $("#invitation_id").trigger("change");
      return false;
    }
    var url = "/app.php/snahp/invite/get_invite_user_json/?u=" + username;
    $.get(url).done((resp) => {
      populate_manager(resp);
      $("#invitation_id").trigger("change");
    });
  });
});
