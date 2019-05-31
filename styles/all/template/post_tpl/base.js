var Post_tpl = {};
Post_tpl.text = '';
Post_tpl.cursor = 0;

Post_tpl.setup_badges = function()
{
    // delete badge css: https://github.com/twbs/bootstrap/issues/18759#issuecomment-322583020
    $wrapper = $('#custom_tpl_badges_body');
    $wrapper.find('.badge').remove();
    $.get('/app.php/snahp/template/get/', (resp)=>{
        for (var entry of resp)
        {
            $badge = $(`<span class="badge badge-primary" data-name="${entry['name']}">${entry['name']}</span>`)
                .click((e)=>{
                    $target = $(e.target);
                    var name = $target.prop('dataset')['name'];
                    Post_tpl.open(name);
                });
            $close = $(`<button type="button" class="close" data-name="${entry['name']}"><span aria-hidden="true" data-name="${entry['name']}">&times;</span></button>`)
                .click((e)=>{
                    $target = $(e.target);
                    var name = $target.prop('dataset')['name'];
                    e.stopPropagation();
                    Post_tpl.delete(name);
                })
                .appendTo($badge);
            $html = $(`<div></div>`);
            $html.append($badge);
            $html.prependTo($wrapper);
        }
    });
}


Post_tpl.focus_form = function($tpl, delay)
{
    setTimeout(function() {
        $input = $tpl.find('input[type="text"]');
        if ($input.length)
        {
            $input.focus();
        }
        else
        {
            $textarea = $tpl.find('textarea');
            if ($textarea.length)
            {
                $textarea = $textarea[0];
                $textarea.focus();
            }
        }
    }.bind(this), delay);
}

Post_tpl.open = function(name)
{
    Post_tpl.cursor = $('#message').prop("selectionStart");
    if (!name)
    {
        return false;
    }
    if (name == 'new')
    {
        $tpl = $('#custom_tpl_create_modal');
        $tpl.modal('show');
        Post_tpl.focus_form($tpl, 100);
    }
    else
    {
        var p = {'n': name, 'f': 1};
        $('[name="custom_tpl_name"]').val(name);
        $.get(`/app.php/snahp/template/get/?name=${p['n']}&full=${p['f']}`, (resp)=>{
            Post_tpl.create_fields(resp[0]);
            $('#custom_tpl_modal_title').text(name);
        });
        $tpl = $('#custom_tpl_details_modal');
        $tpl.modal('show');
        Post_tpl.focus_form($tpl, 250);
    }
}

Post_tpl.create = function()
{
    $form = $('#custom_tpl_create_modal');
    var name = $form.find('.custom_tpl_new_name').val();
    var text = $form.find('.custom_tpl_new_text').val();
    var url = `/app.php/snahp/template/create/`;
    var data = {
        'text': text,
        'name': name,
    };
    $.ajax({
        type: 'post',
        dataType: 'json',
        url: url,
        data: data,
        success: (resp)=>{
            Post_tpl.setup_badges();
        }
    });
}

Post_tpl.extract_fields = function(text)
{
    var data = new Set([]);
    var regex = /\(\(([0-9a-zA-z]*)\)\)/gm;
    while(match = regex.exec(text))
    {
        data.add(match[1]);
    }
    return Array.from(data);
}

Post_tpl.create_fields = function(resp)
{
    var text = resp['text'];
    var data = Post_tpl.extract_fields(text);
    $body = $('#custom_tpl_body').empty();
    for (var key in data) {
        var tmp = {'key': key, 'val': data[key]}
        var html = `
            <div class="input-group input-group-sm mb-3">
              <div class="input-group-prepend custom_tpl_textarea_prepend">
                <span class="input-group-text">${tmp['val']}</span>
              </div>
              <textarea data-name="${tmp['val']}" "type="text" rows="1" class="mb-textarea form-control custom_tpl_field"></textarea>
            </div>
        `;
        $entry = $(html).appendTo($body);
    }
    html = `
<div class="row">
    <div class="col-sm-12">
        <div class="form-group">
        <textarea class="form-control" id="custom_tpl_textarea" rows="6">((body))</textarea>
        </div>
    </div>
</div>`;
    Post_tpl.text = resp['text'];
    html = html.replace('((body))', Post_tpl.text);
    $entry = $(html).appendTo($body);
}

Post_tpl.fill_message = function(message)
{
    var orig = $('#message').val();
    var prev = orig.slice(0, Post_tpl.cursor);
    var nexx = orig.slice(Post_tpl.cursor);
    $('#message').val(prev + message + nexx);
}

Post_tpl.delete = function(name)
{
    var user_id = $('[name="snp_user_id"]').val();
    var p = {'n': name, 'u': user_id};
    var url = `/app.php/snahp/template/delete/?u=${p['u']}&n=${p['n']}`;
    $.get(url, (resp)=>{
        Post_tpl.setup_badges();
    });
}

Post_tpl.save = function()
{
    var name = $('[name="custom_tpl_name"]').val();
    var p = {'n': name};
    var url = `/app.php/snahp/template/save/?n=${p['n']}`;
    var data = {
        'text': $('#custom_tpl_textarea').val(),
        'name': name,
    };
    $.ajax({
        type: 'post',
        dataType: 'json',
        url: url,
        data: data,
        success: (resp)=>{
            Post_tpl.setup_badges();
            Post_tpl.open(name);
        }
    });
}

Post_tpl.fill_form = function()
{
    $fields = $('[class$="form-control custom_tpl_field"]');
    $field_values = [];
    for (var i = 0, len = $fields.length; i < len; i++) {
        $field  = $($fields[i]);
        $fieldname = $field.prop('dataset')['name'];
        $field_values[$fieldname] = $field.val();
    }
    var new_tpl_text = Post_tpl.text;
    for (var key in $field_values)
    {
        if (key)
        {
            var ptn = '\\(\\(' + key + '\\)\\)';
            ptn = new RegExp(ptn, 'g');
            new_tpl_text = new_tpl_text.replace(ptn, $field_values[key]);
        }
    }
    Post_tpl.fill_message(new_tpl_text);
}

$(function() {
    Post_tpl.setup_badges();
});
