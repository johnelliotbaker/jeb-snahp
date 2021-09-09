var Invite_giveaways = {};

Invite_giveaways.prog_tpl = `<div class="progress">
  <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
</div>`;
Invite_giveaways.btn_tpl =
  '<button id="confirm_btn" type="button" class="btn btn-primary">Submit</button>';

Invite_giveaways.create_confirm = function (gid) {
  var val = [];
  Invite_giveaways.prepare_confirmation_modal();
  $confirm_body = $("#confirm_body");
  $group_name = $("#group_name_" + gid);
  val["group_name"] = $group_name.text();
  var strn = `<h6>Are you sure you want to create user entries for all members in <span style="color:#CC0000; font-weight:900;">${val["group_name"]}</span>?<br><br>
        This may take a long time.</h6>`;
  $confirm_body.html(strn);
  $elem_n = $("#n_" + gid);
  var n_invite = $elem_n.val();
  $("#confirm_btn").attr({ onclick: "Invite_giveaways.create(" + gid + ");" });
  $("#confirm_modal").modal();
};

Invite_giveaways.create = function (gid) {
  $confirm_body = $("#confirm_body").empty();
  $prog = $(Invite_giveaways.prog_tpl);
  var url =
    "/app.php/snahp/acp_invite/insert_invite_users_by_group/?gid=" + gid;
  es = new EventSource(url);
  es.addEventListener("message", (resp) => {
    var data = JSON.parse(resp.data);
    if (!data) return;
    var status = data.status;
    switch (status) {
      case "START":
        $("#confirm_title").text("Creating Invite Users...");
        $prog.appendTo($confirm_body);
        $progressbar = $(".progress-bar");
        $progressbar.css(
          "width",
          Math.round((100 * data.i) / data.total) + "%"
        );
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        $("#confirm_btn").remove();
        $("#confirm_cancel_btn").addClass("hidden");
        $("#confirm_close_btn").removeClass("hidden");
        break;
      case "PROGRESS":
        $progressbar = $(".progress-bar");
        $progressbar.css(
          "width",
          Math.round((100 * data.i) / data.total) + "%"
        );
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        break;
      case "COMPLETE":
        $("#confirm_title").text("Creating Invite Users... Done");
        $progressbar = $(".progress-bar");
        $progressbar.css("width", Math.round(100) + "%");
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        es.close();
        break;
      case "ERROR":
        es.close();
        break;
      default:
    }
  });
  es.addEventListener("error", (resp) => {
    es.close();
  });
};

Invite_giveaways.prepare_confirmation_modal = function () {
  $("#confirm_btn").remove();
  $("#confirm_title").text("Please confirm:");
  $("#confirm_cancel_btn").removeClass("hidden");
  $("#confirm_close_btn").addClass("hidden");
  $btn = $(Invite_giveaways.btn_tpl);
  $confirm_footer = $("#confirm_footer");
  $confirm_footer.prepend($btn);
};

Invite_giveaways.send_confirm = function (gid) {
  var val = [];
  Invite_giveaways.prepare_confirmation_modal();
  $confirm_body = $("#confirm_body");
  $group_name = $("#group_name_" + gid);
  val["group_name"] = $group_name.text();
  $n_invite = $("#n_" + gid);
  val["n_invite"] = $n_invite.val();
  var strn = `<h6>Are you sure you want to send ${val["n_invite"]} invites to all members in <span style="color:#CC0000; font-weight:900;">${val["group_name"]}</span>?<br><br>
        This may take a long time.</h6>`;
  $confirm_body.html(strn);
  $("#confirm_btn").attr({ onclick: "Invite_giveaways.send(" + gid + ");" });
  $("#confirm_modal").modal();
};

Invite_giveaways.send = function (gid) {
  var val = [];
  $confirm_body = $("#confirm_body").empty();
  $prog = $(Invite_giveaways.prog_tpl);
  $group_name = $("#group_name_" + gid);
  val["group_name"] = $group_name.text();
  $n_invite = $("#n_" + gid);
  var n_invite = $n_invite.val();
  var url =
    "/app.php/snahp/acp_invite/giveaway_invtes/?gid=" + gid + "&n=" + n_invite;
  val["n_invite"] = n_invite;
  es = new EventSource(url);
  var base_title_strn = `Giving ${val["n_invite"]} Invites to ${val["group_name"]} ...`;
  es.addEventListener("message", (resp) => {
    var data = JSON.parse(resp.data);
    if (!data) return;
    var status = data.status;
    switch (status) {
      case "START":
        $("#confirm_title").text(base_title_strn);
        $prog.appendTo($confirm_body);
        $progressbar = $(".progress-bar");
        $progressbar.css(
          "width",
          Math.round((100 * data.i) / data.total) + "%"
        );
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        $("#confirm_btn").remove();
        $("#confirm_cancel_btn").addClass("hidden");
        $("#confirm_close_btn").removeClass("hidden");
        break;
      case "PROGRESS":
        $progressbar = $(".progress-bar");
        $progressbar.css(
          "width",
          Math.round((100 * data.i) / data.total) + "%"
        );
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        break;
      case "COMPLETE":
        $("#confirm_title").text(base_title_strn + " Done");
        $progressbar = $(".progress-bar");
        $progressbar.css("width", Math.round(100) + "%");
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        es.close();
        break;
      case "ERROR":
        es.close();
        break;
      default:
    }
  });
  es.addEventListener("error", (resp) => {
    es.close();
  });
};

Invite_giveaways.delete_confirm = function (gid) {
  var val = [];
  Invite_giveaways.prepare_confirmation_modal();
  $confirm_body = $("#confirm_body");
  $group_name = $("#group_name_" + gid);
  val["group_name"] = $group_name.text();
  $n_invite = $("#n_" + gid);
  val["n_invite"] = $n_invite.val();
  var strn = `<h6>Are you sure you want to delete valid invites from all members in <span style="color:#CC0000; font-weight:900;">${val["group_name"]}</span>?<br><br>
        This may take a long time.</h6>`;
  $confirm_body.html(strn);
  $("#confirm_btn").attr({
    onclick: "Invite_giveaways.delete_valid(" + gid + ");",
  });
  $("#confirm_modal").modal();
};

Invite_giveaways.delete_valid = function (gid) {
  var val = [];
  $confirm_body = $("#confirm_body").empty();
  $prog = $(Invite_giveaways.prog_tpl);
  $group_name = $("#group_name_" + gid);
  val["group_name"] = $group_name.text();
  $n_invite = $("#n_" + gid);
  var n_invite = $n_invite.val();
  var url = "/app.php/snahp/acp_invite/delete_valid/?gid=" + gid;
  es = new EventSource(url);
  var base_title_strn = `Deleting All Valid Invites from ${val["group_name"]} ...`;
  es.addEventListener("message", (resp) => {
    var data = JSON.parse(resp.data);
    if (!data) return;
    var status = data.status;
    switch (status) {
      case "START":
        $("#confirm_title").text(base_title_strn);
        $prog.appendTo($confirm_body);
        $progressbar = $(".progress-bar");
        $progressbar.css(
          "width",
          Math.round((100 * data.i) / data.total) + "%"
        );
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        $("#confirm_btn").remove();
        $("#confirm_cancel_btn").addClass("hidden");
        $("#confirm_close_btn").removeClass("hidden");
        break;
      case "PROGRESS":
        $progressbar = $(".progress-bar");
        $progressbar.css(
          "width",
          Math.round((100 * data.i) / data.total) + "%"
        );
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        break;
      case "COMPLETE":
        $("#confirm_title").text(base_title_strn + " Done");
        $progressbar = $(".progress-bar");
        $progressbar.css("width", Math.round(100) + "%");
        $progressbar.text(`${data["i"]} / ${data["total"]}`);
        es.close();
        break;
      case "ERROR":
        es.close();
        break;
      default:
    }
  });
  es.addEventListener("error", (resp) => {
    es.close();
  });
};

Invite_giveaways.select = function (tab) {
  var tabs = [1, 2, 3];
  for (var i of tabs) {
    if (i == tab) {
      $("#help" + i).removeClass("hidden");
      $("#tab" + i).addClass("active");
    } else {
      $("#help" + i).addClass("hidden");
      $("#tab" + i).removeClass("active");
    }
  }
};

$(document).ready(() => {});
