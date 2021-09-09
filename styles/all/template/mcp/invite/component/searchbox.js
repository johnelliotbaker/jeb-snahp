function make_clickable_username(resp, $parent) {
  $searchbox = $("#username_searchbox");
  for (var username of resp) {
    $elem = $(
      '<button type="button" style="margin-left: 10px;" class="badge btn-primary pull-left" id="partial_' +
        username +
        '">' +
        username +
        "</span>"
    );
    $elem.click((e) => {
      var username = $(e.target).text();
      $searchbox.val(username);
      $parent.empty();
      $("#list_btn").click();
    });
    $elem.appendTo($parent);
  }
}

$(function () {
  $ajax_usernames = $("#ajax_usernames");
  $searchbox = $("#username_searchbox");
  var base_url = "/app.php/snahp/userscript/username/?partial=";
  $searchbox.keyup((e) => {
    var partial = $searchbox.val();
    var url = base_url + partial;
    $ajax_usernames.empty();
    if (partial.length > 2) {
      $.get(url).done((resp) => {
        make_clickable_username(resp, $ajax_usernames);
      });
    }
  });
});
