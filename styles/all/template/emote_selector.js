var qwer = {};

qwer.selection_start = 0;
qwer.dialog = null;
qwer.ready = false;

qwer.detect_multi_click = function($textarea, count)
{
    // https://stackoverflow.com/questions/6480060/how-do-i-listen-for-triple-clicks-in-javascript
    $textarea[0].addEventListener('click', (evt)=>{
        if (evt.detail === count) {
            qwer.selection_start = $textarea[0].selectionStart;
            qwer.show();
        }
    });
}

qwer.open_modal = function()
{
    qwer.selection_start = $textarea[0].selectionStart;
    qwer.show();
}

qwer.template = `
<div class="twbs">
	<div class="modal" id="emotes_selector" tabindex="-1" role="dialog">
	  <div class="modal-dialog" role="document">
		<div class="modal-content">
		  <div class="modal-header pt-reduced pb-reduced">
			<h5 class="modal-title"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
		  </div>
		  <div class="modal-body" id="emotes_selector_content">
		  </div>
		</div>
	  </div>
	</div>
</div>
`;

qwer.show = function()
{
    qwer.dialog.modal('show');
}

qwer.insert = function(uid)
{
    $textarea = $('textarea[name="message"]');
    var text = $textarea.val();
    var s = qwer.selection_start;
    var before = text.substr(0, s);
    var after = text.substr(s);
    text = before + '\#' + uid + '#' + after;
    $textarea.val(text);
    qwer.selection_start += uid.length + 2;
}

qwer.make_emote = function(url, uid)
{
    var strn = '<img onClick=qwer.insert("' + uid + '"); class="emotes_small" src="' + url + '"></img>';
    return strn;
}

qwer.setup_emotes = function()
{
    var url = '/app.php/snahp/emotes/ls/';
    $.get(url).done((resp)=>{
        var status = resp.status;
        if (status == -1)
        {
            qwer.ready = false;
            return false;
        }
        var data = resp.data;
        $content = $('#emotes_selector_content');
        $content.empty();
        for(var key in data)
        {
            if (!key.match(/e_[a-z]{2}[0-9]{3}/g)) { continue; }
            var entry = data[key];
            var url = entry.url;
            var emote_html = qwer.make_emote(url, key);
            $content.append(emote_html);
        }
        qwer.ready = true;
    });
};

$(function () {
    // Check that message box is available to insert emotes
    $textarea = $('textarea[name="message"]');
    if ($textarea.length == 0)
    {
        return;
    }
    qwer.setup_emotes();
    // Modal setup
    $emote_selector_dialog = $(qwer.template).appendTo($("body"));
    $emote_selector_dialog = $("#emote_selector_dialog");
    qwer.dialog = $('#emotes_selector');
    // Set up keyboard and mouse shortcut
    $textarea.keydown((e)=>{
        if (e.ctrlKey && e.keyCode == 77)
        {
            if (qwer.ready)
            {
                qwer.dialog.modal('show');
            }
            qwer.selection_start = $textarea[0].selectionStart;
        }
    });
    // Double clicking on the textarea will open the modal
    // qwer.detect_multi_click($textarea, click_count=2);
});
