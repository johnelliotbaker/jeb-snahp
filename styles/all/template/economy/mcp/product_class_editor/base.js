var pce = {};

pce.fetch_data = function (target) {};

pce.save = function (event) {
  $target = $(event.target);
  $target.removeClass("btn-success");
  $target.addClass("btn-danger");
  $target.prop("disabled", true);
  $parent = $target.closest("tr");
  $children = $parent.children();
  var data = {};
  $children.each((i) => {
    $child = $($children[i]);
    $input = $child.find("input");
    var name = $input.prop("name");
    var val = $input.val();
    if (name !== undefined) {
      data[name] = val;
    }
  });
  var json = JSON.stringify(data);
  var url = "/app.php/snahp/economy/pce/save/?json=" + json;
  $.get(url).done((resp) => {
    setTimeout(
      function () {
        if (resp.status === "success") {
          $target.removeClass("btn-danger");
          $target.addClass("btn-success");
          $target.prop("disabled", false);
        } else {
          console.log(resp);
        }
      }.bind(this),
      500
    );
  });
};
