<?php
namespace jeb\snahp\core\template;

class gallery
{
    protected $def;
    protected $allowed_interpreted_tags;
	public function __construct(
	)
	{
        $column_sizes = [
            'sm'      => 'col-lg-2 col-3 sm',
            'default' => 'col-lg-3 col-sm-4 col-6 default',
            'lg'      => 'col-lg-4 col-sm-6 col-12 lg',
        ];
        $def['column_sizes'] = $column_sizes;
        $this->def = $def;
        $this->allowed_interpreted_tags = join('|', ['b', 'sm', 'br']);
	}

    public function handle($mode, $data, $options=[])/*{{{*/
    {
        switch ($mode)
        {
        case 'compact':
            return $this->handle_compact($data, $options);
        case 'grid':
            return $this->handle_grid($data, $options);
        case 'cards':
            return $this->handle_cards($data, $options);
        default:
            break;
        }
        return '';
    }/*}}}*/

    private function handle_cards($data, $options=[])/*{{{*/
    {
        $column_size = $this->def['column_sizes'][$options['size']];
        $html['begin'][] = '
<link rel="stylesheet" type="text/css" href="/ext/jeb/snahp/styles/all/template/gallery/component/cards/base.css">
<div class="twbs">
<section class="gallery-block cards-gallery">
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
            $d[1] = preg_replace('#&lt;((/)?(' . $this->allowed_interpreted_tags . '))&gt;#', '<\1>', $d[1]);
            $body[] = '<div class="' . $column_size . ' item' . $cls . '"> 
	                <div class="card border-0 transform-on-hover">
                        <div class="gallery_cards clipboard" onClick="Clipboard.copy_gallery_link(event);">
                            <i class="icon fa-clipboard fa-fw icon-black" aria-hidden="true"></i>
                        </div>
	                	<' . $el . ' href="' . $link . '" target="_blank">
	                		<img src="' . $d[3] . '" alt="Card Image" class="card-img-top">
                            <div class="hiddencorner ' . $cls . '">
                                <img src="https://i.imgur.com/Q07cXb4.png">
                            </div>
	                	</' . $el . '>
	                    <div class="card-body">
	                        <h6>'. $d[0] . '</h6>
	                        <p class="text-muted card-text">' . $d[1] . '</p>
	                    </div>
	                </div>
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

    private function handle_compact($data, $options=[])/*{{{*/
    {
        $column_size = $this->def['column_sizes'][$options['size']];
        $html['begin'][] = '
<link rel="stylesheet" type="text/css" href="/ext/jeb/snahp/styles/all/template/gallery/component/compact/base.css">
<div class="twbs">
<section class="gallery-block compact-gallery">
  <div class="container">
    <div class="row no-gutters">
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
            $d[1] = preg_replace('#&lt;((/)?(' . $this->allowed_interpreted_tags . '))&gt;#', '<\1>', $d[1]);
            $body[] = '<div class="' . $column_size . ' item zoom-on-hover' . $cls . '"> 
                <div class="gallery_compact clipboard" onClick="Clipboard.copy_gallery_link(event);">
                    <i class="icon fa-clipboard fa-fw icon-black" aria-hidden="true"></i>
                </div>
                <' . $el . ' href="' . $link . '" target="_blank">
                  <img class="img-fluid image" src="' . $d[3] . '">
                  <div class="hiddencorner ' . $cls . '">
                      <img src="https://i.imgur.com/Q07cXb4.png">
                  </div>
                  <span class="description">
                    <span class="description-heading">' . $d[0] . '</span>
                    <span class="description-body">' . $d[1] . '</span>
                  </span>
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
