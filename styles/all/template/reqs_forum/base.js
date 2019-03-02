var reqs_tags_selector_template = `
<style>
.btn.selector
{
    padding:10px;
}
span.btn.all {
    background:#111;
    border: 1px solid #777;
}
li.row.bg1.hidden,
li.row.bg2.hidden
{
    display: none;
}
</style>
<div id="reqs_tags_selector" align="right">
<span onClick="select_tags('all')" class="btn all selector"></span>
<span onClick="select_tags('open')" class="btn open selector"></span>
<span onClick="select_tags('dib')" class="btn dib selector"></span>
<span onClick="select_tags('fulfill')" class="btn fulfill selector"></span>
<span onClick="select_tags('solve')" class="btn solve selector"></span>
<span onClick="select_tags('terminate')" class="btn terminate selector"></span>
</div>
`

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
    $bar_top = $('.action-bar.bar-top');
    $(reqs_tags_selector_template).insertAfter($bar_top);
    if (!cookie)
    {
        cookie = 'all';
        createCookie('requests_selector_cookie', cookie, 365);
    }
    hide_tags(cookie);
});
