// Season Theme
var Seasonal_theme = {};

Seasonal_theme.modify_elem_list = [ '.headerbar', '.site_logo', '.logo', '.site-description'];

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
  if (enable===undefined || enable)
  {
    for (var elem_name of this.modify_elem_list)
    {
      $(elem_name).addClass('thanksgiving');
    }
  }
  else
  {
    for (var elem_name of this.modify_elem_list)
    {
      $(elem_name).removeClass('thanksgiving');
    }
  }
}
