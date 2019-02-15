$(document).ready(()=>{
    // Hexagon theme. When notification badge turns 0, remove element
    $('body').on('DOMSubtreeModified', 'strong.navbar_counter.badge', (e)=>{
        $badge = $('strong.navbar_counter.badge');
        if ($badge.text() == "0") { $badge.remove(); }
    });
    // Remove Unanswered topics from quick links
    $('span:contains("Unanswered topics")').closest('li').remove();
    // Auto focus on username when logged out
    $('input#username').focus();
    // remove quick reply's automatic signature attachment
    $("input:hidden[name='attach_sig']").remove();
});
