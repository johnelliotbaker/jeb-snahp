var CustomRank = {};

CustomRank.save = function()
{
    var data = {};
    data['rt'] = encodeURIComponent($('#custom_rank_title').val());
    data['ri'] = encodeURIComponent($('#custom_rank_img').val());
    var url = `/app.php/snahp/custom_rank/save/?rt=${data['rt']}&ri=${data['ri']}`;
    $.get(url).done((resp)=>{
        this.update();
    })
}

CustomRank.update = function()
{
    $container = $('#custom_rank_save_button');
    $container.html(`
        <div id="spinner" class="spinner-border" role="status">
        </div>
        `);
    var url = `/app.php/snahp/custom_rank/get_info/`;
    $.get(url).done((resp)=>{
        let title = resp[0];
        let img_url = resp[1];
        $('#custom_rank_title').val(title);
        $('#custom_rank_img').val(img_url);
        setTimeout(function() {
            $container.html(`<div id="spinner" role="status"><b>Save</b></div>`);
        }.bind(this), 300);
    })
}

$(function () {
    CustomRank.update();
});
