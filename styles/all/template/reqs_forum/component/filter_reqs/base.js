var Filter_reqs = {};

Filter_reqs.select_tags = function (selection) {
  Cookie.set("requests", "filter.selection", selection);
  Filter_reqs.hide_tags(selection);
};

Filter_reqs.hide_tags = function (selection = "all") {
  $a_btn = $(".btn");
  $.each($a_btn, (index) => {
    $btn = $($a_btn[index]);
    var b_match = $btn.hasClass(selection) && !$btn.hasClass("selector");
    $elem = $btn.closest("li");
    if ($elem) {
      if (b_match || selection == "all") {
        $elem.removeClass("hidden");
      } else {
        $elem.addClass("hidden");
      }
    }
  });
};

var selection = Cookie.get("requests", "filter.selection");
if (!selection) {
  var selection = "all";
  Cookie.set("requests", "filter.selection", selection);
}
Filter_reqs.hide_tags(selection);
