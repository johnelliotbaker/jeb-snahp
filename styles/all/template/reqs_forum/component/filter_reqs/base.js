function select_tags(mode)
{
    createCookie('requests_selector_cookie', mode, 365);
    hide_tags(mode);
}

function hide_tags(mode)
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

$(function () {
    var cookie = readCookie('requests_selector_cookie');
    if (!cookie)
    {
        cookie = 'all';
        createCookie('requests_selector_cookie', cookie, 365);
    }
    hide_tags(cookie);
});
