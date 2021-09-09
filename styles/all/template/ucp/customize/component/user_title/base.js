var CustomRank = {};

CustomRank.save = function () {
  var data = {};
  data["rt"] = encodeURIComponent($("#custom_rank_title").val());
  data["ri"] = encodeURIComponent($("#custom_rank_img").val());
  var url = `/app.php/snahp/custom_rank/save/?rt=${data["rt"]}&ri=${data["ri"]}`;
  $.get(url).done((resp) => {
    this.update();
  });
};

CustomRank.update = function () {
  $btn = $("#custom_rank_save_button");
  $btn.prop("disabled", true);
  $container = $("#custom_rank_spinner");
  $text = $("#custom_rank_text");
  $container.addClass("spinner-border spinner-border-sm");
  $text.text("Loading...");
  $btn.removeClass("btn-success");
  $btn.addClass("btn-secondary");
  var url = `/app.php/snahp/custom_rank/get_info/`;
  $.get(url).done((resp) => {
    let title = resp[0];
    let img_url = resp[1];
    $("#custom_rank_title").val(title);
    $("#custom_rank_img").val(img_url);
    setTimeout(
      function () {
        $container.removeClass("spinner-border spinner-border-sm");
        $text.text("Success!");
        setTimeout(
          function () {
            $text.text("Save");
            $btn.removeClass("btn-secondary");
            $btn.addClass("btn-success");
            $btn.prop("disabled", false);
          }.bind(this),
          1200
        );
      }.bind(this),
      1200
    );
  });
};

$(function () {
  CustomRank.update();
});
