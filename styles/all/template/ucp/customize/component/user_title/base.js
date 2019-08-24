var CustomRank = {};

CustomRank.save = function()
{
    var data = {};
    data['rt'] = encodeURIComponent($('#custom_rank_title').val());
    data['ri'] = encodeURIComponent($('#custom_rank_img').val());
    var url = `/app.php/snahp/custom_rank/save/?rt=${data['rt']}&ri=${data['ri']}`;
    console.log(url);
    $.get(url).done((resp)=>{
        console.log(resp);
        this.update();
    })
}

CustomRank.update = function()
{
    $container = $('#loading');
    $container.html(`
        <div class="spinner-border text-secondary" role="status">
            <span class="sr-only">Loading...</span>
        </div>`);
    var url = `/app.php/snahp/custom_rank/get_info/`;
    console.log(url);
    $.get(url).done((resp)=>{
        let title = resp[0];
        let img_url = resp[1];
        $('#custom_rank_title').val(title);
        $('#custom_rank_img').val(img_url);
        setTimeout(function() {
            $container.empty();
        }.bind(this), 300);
    })
}

$(function () {
    CustomRank.update();
});
