function addOrUpdateUrlParam(name, value) {
  var href = window.location.href;
  if (href[href.length - 1] == "#") {
    href = href.substring(0, href.length - 1);
  }
  var regex = new RegExp("[&\\?]" + name + "=");
  if (regex.test(href)) {
    regex = new RegExp("([&\\?])" + name + "=\\w+");
    window.location.href = href.replace(regex, "$1" + name + "=" + value);
  } else {
    if (href.indexOf("?") > -1)
      window.location.href = href + "&" + name + "=" + value;
    else window.location.href = href + "?" + name + "=" + value;
  }
}
