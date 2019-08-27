<?php
namespace jeb\snahp\core\template;

class imgcompare
{
	public function __construct(
	)
	{
	}

    public function to_html($arg)/*{{{*/
    {
        $args = explode('`', $arg);
        if (count($args) < 2) { return ''; }
        for ($i=count($args); $i<4; $i++)
        {
            $args[$i] = '';
        }
        $url1 = $args[0];
        $url2 = $args[1];
        $label1 = $args[2];
        $label2 = $args[3];
        if (!filter_var($url1, FILTER_VALIDATE_URL) || !filter_var($url2, FILTER_VALIDATE_URL)) { 
            return 'First two arguments must be valid URL.';
        }
        $uuid = uniqid('imgcompare_');
        $head = '
<div onClick="send_resize_event();"
	class="imgcompare_thumbnail_container pointer noselect"
    data-toggle="modal"
    data-target="#' . $uuid . '">
    <div class="crop_top">
        <img src="'. $url1 .'" class="imgcompare_thumbnail_left">
    </div>
    <div class="crop_bottom">
        <img src="'. $url2 .'" class="imgcompare_thumbnail_right">
    </div>
</div>
<div class="twbs">
  <div class="modal fade" id="' . $uuid . '" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </div>
        <div class="modal-body p-1">
';
        $tail = '
        </div>
      </div>
    </div>
  </div>
</div>
';
        $body = '
          <div style="max-height:95vh;" id="slider1" class="beer-slider" data-beer-label="' . $label1 . '" data-start="50">
            <img style="width:100%;" src="' . $url1 . '" alt="' . $label1 . '">
            <div class="beer-reveal" data-beer-label="' . $label2 . '">
              <img src="' . $url2 . '" alt="' . $label2 . '">
            </div>
          </div>
';
        $res = [$head, $body, $tail];
        $html = join(PHP_EOL, $res);
        return $html;
    }/*}}}*/

    private function handle_grid($data, $options=[])/*{{{*/
    {
        $column_size = $this->def['column_sizes'][$options['size']];
        $html['begin'][] = '
<link rel="stylesheet" type="text/css" href="/ext/jeb/snahp/styles/all/template/gallery/component/grid/base.css">
<div class="twbs">
<section class="gallery-block grid-gallery">
	<div class="container">
		<div class="row">
';
        $html['end'][] = '
    </div>
  </div>
</section>
</div>';
        $ptn = '<dl class="hidebox (\w+)">';
        $class = ['', ' hi'];
        $elem = ['a', 'span'];
        foreach($data as $d)
        {
            $link = strip_tags($d[2]);
            $choice = 0;
            preg_match($ptn, $d[2], $match);
            if (count($match)>0)
            {
                if($match[1]=='hi')
                {
                    $choice = 1;
                }
            }
            $cls = $class[$choice];
            $el = $elem[$choice];
            $body[] = '<div class="' . $column_size . ' item' . $cls . '"> 
                           <' . $el . ' href="' . $link . '" target="_blank">
                               <img class="img-fluid image scale-on-hover" src="' . $d[3] . '">
                           </' . $el . '>
                       </div>';
        }
        $html['body'] = $body;
        $sequence = ['begin', 'body', 'end'];
        $res = '';
        foreach ($sequence as $key)
        {
            $res .= join(PHP_EOL, $html[$key]);
        }
        return $res;
    }/*}}}*/

}
