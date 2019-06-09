var Filter_reqs = {};

Filter_reqs.select_tags = function(mode)
{
    Cookie.set('requests', 'filter.selection', mode);
    // createCookie('requests_selector_cookie', mode, 365);
    Filter_reqs.hide_tags(mode);
}

Filter_reqs.hide_tags = function(mode)
{
    $a_btn = $('.btn');
    $.each($a_btn, (index)=>{
        $btn = $($a_btn[index]);
        var b_match = $btn.hasClass(mode) && !$btn.hasClass('selector');
        $elem = $btn.closest('li');
        if ($elem)
        {
            if (b_match || mode=='all')
            {
                $elem.removeClass('hidden');
            }
            else
            {
                $elem.addClass('hidden');
            }
        }
    });
}

var cookie = Cookie.get('requests', 'filter.selection');
if (!cookie)
{
    var selection = 'all';
    Cookie.set('requests', 'filter.selection', selection);
}
Filter_reqs.hide_tags(cookie);
