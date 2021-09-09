// Function definitions
function get(name) {
  if (
    (name = new RegExp("[?&]" + encodeURIComponent(name) + "=([^&]*)").exec(
      location.search
    ))
  )
    return decodeURIComponent(name[1]);
}

function hide($elem) {
  $elem.addClass("hidden");
}

function show($elem) {
  $elem.removeClass("hidden");
}

$(function () {
  $searchbox = $("#username_searchbox");
  $searchbox.keydown((event) => {
    if (event.keyCode == 13) {
      event.preventDefault();
      $("#list_btn").trigger("click");
    }
  });
});
