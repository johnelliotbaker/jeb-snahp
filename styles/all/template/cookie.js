function createCookie(name, value, days) {
    var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    var servername = document.domain;
    servername = $.isNumeric(servername.substring(0,1)) ? servername : '.' + servername;
    const domain = " domain=" + servername + ";";
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/;" + domain;
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0)
        {
            return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
    }
    return;
}


var Cookie = {};
Cookie.get_prefix = function()
{
    return $('input[name="snp_cookie_prefix"]').val();
}

Cookie.set = function(name, path, val)
{
    var data = Cookie.get(name);
    // If the cookie has never been set, initialize to {}
    if (data===undefined) { data = {}; }
    Dict.set(data, path, val);
    data = JSON.stringify(data);
    name = this.get_prefix() + name;
    createCookie(name, data, 365);
    var data = Cookie.get(name);
}

Cookie.get = function(name, path='')
{
    name = this.get_prefix() + name;
    var data = readCookie(name);
    if (data===undefined) { return; }
    data = JSON.parse(data);
    if (path=='')
    {
        return data;
    }
    var val = Dict.get(data, path)
    return val;
}
