var User_blocker_form = {};

User_blocker_form.setup = function()
{
    this.elem = {};
    this.elem.input = {};
    this.elem.input.post_id = $('input[name=post_id]');
    this.elem.input.emergency_blocked_id = $('input[name=emergency_blocked_id]');
    this.elem.input.triage_username = $('input[name=triage_username]');
    this.elem.input.mode_selector = $('input[name=mode_selector');
    User_blocker_form.register_post_id_parser();
    User_blocker_form.register_mode_selector();
    this.select_mode('normal');
    $('[data-toggle="popover"]').popover();
    $("body").keydown(function(event){
        if(event.keyCode == 27) {
            event.preventDefault();
            $(".modal_imdb").remove();
            $('[data-toggle="popover"]').popover('hide');
        }
    });
}

User_blocker_form.select_mode = function(mode)
{
    if (mode=='triage')
    {
        this.elem.input.post_id.val('');
        this.elem.input.post_id.prop('disabled', true);
        this.elem.input.triage_username.prop('disabled', false);
        this.elem.input.triage_username.focus();
    }
    else
    {
        this.elem.input.triage_username.val('');
        this.elem.input.triage_username.prop('disabled', true);
        this.elem.input.post_id.prop('disabled', false);
        this.elem.input.post_id.focus();
    }
}

User_blocker_form.parse_post_id = function(strn)
{
    var re = /(^[0-9]{1,7}$)/
    var match = re.exec(strn);
    if (match && match.length > 1)
    {
        return match[1];
    }
    var re = /(&|\?){1}p=([0-9]+)/
    var match = re.exec(strn);
    if (match && match.length > 2)
    {
        return match[2];
    }
    var re = /#p([0-9]+)/
    var match = re.exec(strn);
    if (match && match.length > 1)
    {
        return match[1];
    }
    return '';
}

User_blocker_form.register_post_id_parser = function()
{
    this.elem.input.post_id.change((e)=>{
        $target = $(e.target);
        var val = $target.val();
        if (val)
        {
            var post_id = User_blocker_form.parse_post_id(val);
            this.elem.input.post_id.val(post_id);
        }
    })
}

User_blocker_form.register_mode_selector = function()
{
    this.elem.input.mode_selector.change((e)=>{
        $target = $(e.target);
        var val = $target.val();
        if (val==0)
        {
            this.select_mode('triage');
        }
        else
        {
            this.select_mode('normal');
        }
    })
}



$(function () {
    User_blocker_form.setup();
});
