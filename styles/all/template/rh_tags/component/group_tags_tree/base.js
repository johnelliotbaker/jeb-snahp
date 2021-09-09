var Rh_tags_tree = {};

Rh_tags_tree.clear_fields = function () {
  $("input[name=groupname]").val("");
  for (var i = 0, len = 20; i < len; i++) {
    $field = $("input[name=tagname_" + i + "]");
    $field.val("");
  }
};

Rh_tags_tree.append_forum_id = function (e) {
  this.clear_fields();
  $target = $(e.target);
  var groupname = $target.data("groupname");
  $in = $("input[name=groupname]");
  $in.val(groupname);
  $tags = $("#tags_" + groupname);
  var tags = $tags.text().trim().split(", ");
  for (var i = 0, len = tags.length; i < len; i++) {
    var tag = tags[i];
    $field = $("input[name=tagname_" + i + "]");
    $field.val(tag);
  }
};
