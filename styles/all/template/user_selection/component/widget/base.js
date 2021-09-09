var UserSelection = {};
UserSelection.data = {};

UserSelection.make_clickable_username = function (resp, $parent) {
  for (var entry of resp) {
    let username = entry["username"];
    let user_id = entry["user_id"];
    let js = {};
    js["data"] = JSON.stringify(entry);
    $elem = $(
      `<button type="button" data-json='${js["data"]}' style="margin-left: 10px;" class="badge btn-primary pull-left" id="partial_` +
        username +
        '">' +
        username +
        "</span>"
    );
    $elem.click((e) => {
      var username = $(e.target).text();
      UserSelection.searchbox.val();
      $parent.empty();
      UserSelection.data = JSON.parse($(e.target)[0].dataset.json);
      $("#list_btn").click();
    });
    $elem.appendTo($parent);
  }
};

$(function () {
  UserSelection.username_candidates = $("#ajax_usernames");
  UserSelection.searchbox = $("#username_searchbox");
  var base_url = "/app.php/snahp/userscript/userid/?partial=";
  UserSelection.searchbox.keyup((e) => {
    var partial = UserSelection.searchbox.val();
    var url = base_url + partial;
    UserSelection.username_candidates.empty();
    if (partial.length > 2) {
      $.get(url).done((resp) => {
        UserSelection.make_clickable_username(
          resp,
          UserSelection.username_candidates
        );
      });
    }
  });
});
