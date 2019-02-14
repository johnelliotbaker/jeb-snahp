$(document).ready(()=>{
    $('body').on('DOMSubtreeModified', 'strong.navbar_counter.badge', (e)=>{
        $badge = $('strong.navbar_counter.badge');
        if ($badge.text() == "0")
        {
            $badge.remove();
        }
    });
    $('span:contains("Unanswered topics")').closest('li').remove();
    $('input#username').focus();
    $('body').append('<span id="nothing"></span>');
});
