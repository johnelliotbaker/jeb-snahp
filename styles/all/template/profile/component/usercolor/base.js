var UserColor = {};
UserColor.elem = {};
UserColor.elem.preview = $("#usercolor_preview");
UserColor.elem.usercolor_text = $("input[name=custom_usercolor]");
UserColor.elem.save_button = $("#save_usercolor");
UserColor.elem.reset_button = $("#reset_usercolor");
UserColor.elem.save_button.click((event) => {
  var color = UserColor.elem.usercolor_text.val();
  if (color.length == 6) {
    UserColor.elem.preview.css("background-color", "#" + color);
    var url =
      "/app.php/snahp/userscript/set_usercolor/?p={PROFILE_ID}&c=" + color;
    $.get(url).done((resp) => {
      if (resp.status == 1) {
        location.reload();
      }
    });
  }
});
UserColor.elem.reset_button.click((event) => {
  var url = "/app.php/snahp/userscript/set_usercolor/?p={PROFILE_ID}&r=1";
  $.get(url).done((resp) => {
    if (resp.status == 1) {
      location.reload();
    }
  });
});
