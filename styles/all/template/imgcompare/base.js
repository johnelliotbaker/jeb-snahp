// https://github.com/kavyasukumar/imgSlider
function send_resize_event()
{
  for(let i=0; i<5; i++)
  {
    setTimeout(function(){
      window.dispatchEvent(new Event('resize'));
    }, 100*i);
  }
}

$(window).resize(()=>{
  var vh = $(window).height();
  var vw = $(window).width();
  var h = vh*.8;
  var w = vw*.8;
  var img = document.getElementById("img1");
  var nh = img.naturalHeight;
  var nw = img.naturalWidth;
  var ar = nw/nh;
  var ih = img.height;
  var iw = img.wight;
  // img.width = w;
  // $('.modal-dialog').css('max-width', '');
  // $('.modal-dialog').css('width', w + 'px');
})

$(document).ready(function() {
  $.fn.BeerSlider = function( options ) {
    options = options || {};
    return this.each( function () {
      new BeerSlider( this, options );
    });
  };
  $( ".beer-slider" ).each( function( index, el ) {
    $( el ).BeerSlider( {start: $( el ).data( "start" ) } )
  });
});
