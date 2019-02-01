var advanced_height = null;

function toggle_advanced()
{
    $btn = $("#show_advanced");
    $('#request_advanced').animate({ opacity: "toggle", height: "toggle", });
    if ($btn.prop('value'))
    {
        $btn.prop('value', 0);
        $btn.text('Hide Advanced Options');
    }
    else
    {
        $btn.prop('value', 1);
        $btn.text('Show Advanced Options');
    }
}

function eventFire(el, etype, b_ctrl=false){
    // https://stackoverflow.com/questions/2705583/how-to-simulate-a-click-with-javascript
    if (el.fireEvent) {
        el.fireEvent('on' + etype);
    } else {
        var evObj = document.createEvent('Events');
        evObj.initEvent(etype, true, false);
        if (b_ctrl)
        {
            evObj.ctrlKey = true;
        }
        el.dispatchEvent(evObj);
    }
}

$(document).ready(function(){
    var type = $("#request_type").val();
    $postingbox = $('#postingbox');
    $postingbox.parent().find('h3').text('Create a new ' + type + ' request');
    $('#request_advanced').animate({ opacity: "toggle", height: "toggle"}, 0);
    $('#subject').prop({'required': 'required'})
    $('label[for="subject"]').text('Request:');
    $('input[name="preview"]').remove();
    $('input[name="save"]').remove();
    $('.req_cb_label').click((e) => {
        // For firefox compatibility
        $target = $(e.target);
        var id = $target.attr('for');
        var b_ctrl = false;
        if (e.ctrlKey)
        {
            b_ctrl = true;
        }
        eventFire(document.getElementById(id), 'click', b_ctrl);
    });
    $('.req_cb').click((e) => {
        console.log(e);
        $target = $(e.target);
        $label = $target.next();
        if (e.ctrlKey)
        {
            $target.val(2);
            $label.addClass('required');
            $target.prop('checked', true);
        }
        else
        {
            if (!$target.is(':checked'))
            {
                $target.val(1);
                $label.removeClass('required');
            };
        }
    });
    if (type == 'music')
    {
        var background = $('#req_mus_url').css('background');
        $('.request_form').animate({'opacity': '.6'}, 800);
        setTimeout(()=>{
            for (var i=0; i<3; i++)
            {
                $('#req_mus_url').animate({'opacity': 0.1}, 800);
                $('#req_mus_url').animate({'opacity': 1}, 800);
            }
        }, 0);
        setTimeout(()=>{
            $('.request_form').animate({'opacity': '1'}, 1000);
            $('#req_mus_reminder').animate({ opacity: "toggle", height: "toggle", });
        }, 6000);
    }
});
