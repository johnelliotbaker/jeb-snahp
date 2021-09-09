var Wiki_editor = {};

Wiki_editor.get_wiki_editor_name = function () {
  return $("#wiki_editor_name").val();
};

Wiki_editor.delete = function () {
  var name = this.get_wiki_editor_name();
  if (!name) {
    return;
  }
  var url = "/app.php/snahp/wiki_editor/delete/?name=" + name.toString();
  $.get(url).done((resp) => {
    if (resp.status === 1) {
      window.location.href = "/app.php/snahp/wiki_editor/edit/";
    } else {
      console.log("Something went wrong.");
    }
  });
};
