// Dictionary updater from: https://stackoverflow.com/questions/6842795/dynamic-deep-setting-for-a-javascript-object
var Dict = {}
Dict.set = function(object, path, value)
{
    if (!this.is_dict(object))
    {
        return;
    }
    var a = path.split('.');
    var o = object;
    for (var i = 0; i < a.length - 1; i++)
    {
        var n = a[i];
        if (n in o)
        {
            o = o[n];
        }
        else
        {
            o[n] = {};
            o = o[n];
        }
    }
    o[a[a.length - 1]] = value;
} 
Dict.get = function(object, path) {
    if (!this.is_dict(object))
    {
        return;
    }
    var o = object;
    path = path.replace(/\[(\w+)\]/g, '.$1');
    path = path.replace(/^\./, '');
    var a = path.split('.');
    while (a.length) 
    {
        var n = a.shift();
        if (n in o) 
        {
            o = o[n];
        }
        else 
        {
            return;
        }
    }
    return o;
}

// Simple check if dictionary
Dict.is_dict = function(v)
{
    return typeof v==='object' && v!==null && !(v instanceof Array) && !(v instanceof Date);
}
