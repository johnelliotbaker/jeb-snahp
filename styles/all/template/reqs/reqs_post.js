var Reqs_post =  {};

Reqs_post.advanced_height = null;

Reqs_post.toggle_advanced = function()
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

Reqs_post.eventFire = function(el, etype, b_ctrl=false){
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
    $('.req_cb_label').click((e) => {
        // For firefox compatibility
        $target = $(e.target);
        var id = $target.attr('for');
        var b_ctrl = false;
        if (e.ctrlKey)
        {
            b_ctrl = true;
        }
        Reqs_post.eventFire(document.getElementById(id), 'click', b_ctrl);
    });
    $('.req_cb').click((e) => {
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
    if (['music'].includes(type))
    {
        var background = $('#req_mus_url').css('background');
        $('.request_form').animate({'opacity': '0'}, 800);
        setTimeout(()=>{
            for (var i=0; i<4; i++)
            {
                $('#req_mus_url').animate({'opacity': 0.2}, {duration:650, queue:true});
                $('#req_mus_url').animate({'opacity': 1.0}, {duration:650, queue:true});
            }
        }, 0);
        setTimeout(()=>{
            $('.request_form').animate({'opacity': '1'}, 1000);
            $('#req_mus_reminder').animate({ opacity: "toggle", height: "toggle", });
        }, 6000);
    }
});
