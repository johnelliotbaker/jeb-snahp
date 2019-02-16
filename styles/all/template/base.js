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
    // Curlies
    $searchbox = $('input.autofill').keyup((e)=>{
        var element_id = $(e.target).attr('id');
        var uuid = element_id.match(/(\S+)_(\S+)/)[2];
        if (!uuid) return false;
        $rows = $('#table_' + uuid).find('tr');
        var searchterm = $(e.target).val().toLowerCase().split(/\s+/);
        for (var i=0; i<$rows.length; i++)
        {
            for (var st of searchterm)
            {
                $row = $rows.slice(i, i+1);
                $entry_text = $row.text().toLowerCase();
                if (!$entry_text.includes(st))
                {
                    $row.addClass('hidden');
                    break;
                }
                $row.removeClass('hidden');
            }
        }
    });
});
