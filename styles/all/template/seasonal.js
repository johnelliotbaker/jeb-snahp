// Season Theme
var Seasonal_theme = {};
Seasonal_theme.toggle_thanksgiving = function()
{
    var tg_enable = Cookie.get('seasonal', 'thanksgiving_enable');
    if (tg_enable===undefined)
    {
        Cookie.set('seasonal', 'thanksgiving_enable', true);
        var tg_enable = Cookie.get('seasonal', 'thanksgiving_enable');
    }
    if (tg_enable)
    {
        Cookie.set('seasonal', 'thanksgiving_enable', false);
    }
    else
    {
        Cookie.set('seasonal', 'thanksgiving_enable', true);
    }
    window.location = '/';
}

Seasonal_theme.apply_thanksgiving = function()
{
    var enable = Cookie.get('seasonal', 'thanksgiving_enable');
    console.log(enable);
    if (enable===undefined || enable)
    {
        $('.headerbar .inner').prepend('<a class="default-link" href="/"></a>');
        $('.headerbar').addClass('thanksgiving');
        $('#site-description').addClass('thanksgiving');
        $('#search-box').addClass('thanksgiving');
        $('body').addClass('thanksgiving');
        $('html').addClass('thanksgiving');
    }
    else
    {
        $('.headerbar').removeClass('thanksgiving');
        $('#site-description').removeClass('thanksgiving');
        $('#search-box').removeClass('thanksgiving');
        $('body').removeClass('thanksgiving');
        $('html').removeClass('thanksgiving');
    }
}
