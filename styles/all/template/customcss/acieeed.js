// https://stackoverflow.com/questions/1458724/how-do-i-set-unset-a-cookie-with-jquery
function createCookie(name, value, days) {
    var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0)
            return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

var minimizer_template = '<div align="right"><i style="color:black; font-size:2em; cursor:pointer; -webkit-text-stroke-width:2px;-webkit-text-stroke-color:#f8c301" class="icon fa-caret-up fa-fw" aria-hidden="true"></i><span class="sr-only">Minimize</span></div>';
var maximizer_template = '<div align="right"><i style="color:black; font-size:2em; cursor:pointer; -webkit-text-stroke-width:2px;-webkit-text-stroke-color:#f8c301" class="icon fa-caret-down fa-fw" aria-hidden="true"></i><span class="sr-only">Maximize</span></div>';

$(document).ready(()=>{
    $banner = $($('#page-header > div > div')[0]);
    var b_minimize_banner = readCookie('acieeed_minimize');
    if (b_minimize_banner==1)
    {
        $maximizer = $(maximizer_template)
            .click((e)=>{
                createCookie('acieeed_minimize', 0, 365);
                location.reload();
            });
        $banner.remove();
        $($("#page-header > div")[0]).prepend($maximizer);
    }
    else
    {
        $minimizer = $(minimizer_template)
            .click((e)=>{
                createCookie('acieeed_minimize', 1, 365);
                location.reload();
            });
        $banner.prepend($minimizer);
    }
});
