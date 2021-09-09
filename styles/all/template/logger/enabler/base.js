var Logger = {};

Logger.id = {
  viewtopic: {
    checkbox: "cb_log_viewtopic",
  },
  posting: {
    checkbox: "cb_log_posting",
  },
  user_spam: {
    checkbox: "cb_log_user_spam",
  },
};

Logger.enable = function (name, b) {
  b = b ? 1 : 0;
  var url = "/app.php/snahp/logger/enable/?type=" + name + "&val=" + b;
  $.get(url).done((resp) => {
    console.log(resp);
  });
};

Logger.update_checkboxes = function () {
  for (var name of Object.keys(Logger.id)) {
    this.update_checkbox(name);
  }
};

Logger.update_checkbox = function (name) {
  var cb_id = this.id[name]["checkbox"];
  var $cb = $("#" + cb_id);
  var url = "/app.php/snahp/logger/is_log/?type=" + name;
  $.get(url).done((resp) => {
    let checked = resp.status == 1 ? true : false;
    $cb.prop("checked", checked);
  });
};

Logger.register_checkboxes = function () {
  for (var name of Object.keys(Logger.id)) {
    this.register_checkbox(name);
  }
};

Logger.register_checkbox = function (name) {
  var cb_id = this.id[name]["checkbox"];
  var $cb = $("#" + cb_id);
  $cb.change((event) => {
    let b = $cb.prop("checked");
    this.enable(name, b);
  });
};

$(document).ready(function () {
  Logger.update_checkboxes();
  Logger.register_checkboxes();
});
