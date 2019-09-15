// Using the beerslider from
// https://img.shields.io/github/size/pehaa/beerslider

var ImgCompare = {};

ImgCompare.img = {};

ImgCompare.w2h = function(w, ar)
{
    return w/ar;
}

ImgCompare.h2w = function(h, ar)
{
    return h*ar;
}

ImgCompare.generate_modal_body = function(url1, url2, label1='', label2='')
{
    var tpl = `
          <div class="beer-slider" data-beer-label="` + label1 + `" data-start="50">
            <img class="image_left" src="` + url1 + `">
            <div class="beer-reveal" data-beer-label="` + label2 + `">
              <img id="image_right" src="` + url2 + `">
            </div>
          </div>`;
    return tpl;
}

ImgCompare.setup_modal = function(url1, url2, label1='', label2='')
{
    $mbody = $('#imgcompare_modal .modal-body');
    $mbody.html(ImgCompare.generate_modal_body(url1, url2, label1, label2));
    $img1 = $('#imgcompare_modal .image_left');
    $img2 = $('#imgcompare_modal .image_right');
    $img1.prop('src', url1);
    $img2.prop('src', url2);
    $modal = $('#imgcompare_modal').modal('show');
    this.img[0] = $img1;
    this.img[1] = $img2;
    setTimeout(function(){
        ImgCompare.update_modal_window();
        ImgCompare.initbeer();
    }, 50);
}

ImgCompare.update_modal_window = function()
{
    $img1 = this.img[0];
    if (!$img1) return false;
    var nh = $img1[0].naturalHeight;
    var nw = $img1[0].naturalWidth;
    var ar_n = nw/nh;
    var vh = $(window).height()*.95;
    var vw = $(window).width()*.90;
    var ar_v = vw/vh;
    var h, w;
    if (ar_v > ar_n)
    {
        h = vh;
        w = this.h2w(h, ar_n);
    }
    else
    {
        w = vw;
        h = this.w2h(w, ar_n);
    }
    $modal_dialog = $('#imgcompare_modal .modal-dialog');
    $modal_dialog.width(w);
    $modal_dialog.height(h);
    $modal_content = $('#imgcompare_modal .modal-content');
    $modal_content.width('auto');
    $modal_dialog.css('marginLeft', 'auto');
    var ml = $modal_dialog.css('marginLeft');
    var wc = $modal_content.width();
    var wi = $img1.width();
    ml = parseInt(ml.substring(0, ml.length-2));
    if (wc > wi)
    {
        ml += (wc-wi)*.5;
        $modal_dialog.css('marginLeft', ml);
        $modal_content.width($img1.width());
    }
}

ImgCompare.initbeer = function()
{
    $.fn.BeerSlider = function( options ) {
        options = options || {};
        return this.each( function () {
            new BeerSlider( this, options );
        });
    };
    $( ".beer-slider" ).each( function( index, el ) {
        $( el ).BeerSlider( {start: $( el ).data( "start" ) } )
    });
}

