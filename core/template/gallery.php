<?php
namespace jeb\snahp\core\template;

function prn($var, $b_html=false) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v, $b_html); }
    } else {
        if ($b_html)
        {
            echo htmlspecialchars($var) . '<br>';
        }
        else
        {
            echo $var . '<br>';
        }
    }
}

class gallery
{
	public function __construct(
	)
	{
	}

    public function handle($mode, $data)
    {
        switch ($mode)
        {
        case 'compact':
            return $this->handle_compact($data);
        case 'grid':
            return $this->handle_grid($data);
        case 'cards':
            return $this->handle_cards($data);
        default:
            break;
        }
        return '';
    }

    private function handle_cards($data)
    {
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
        foreach($data as $d)
        {
            $body[] = '
                <div class="col-lg-3 col-sm-4 col-6 item">
	                <div class="card border-0 transform-on-hover">
	                	<a href="' . $d[2] . '" target="_blank">
	                		<img src="' . $d[3] . '" alt="Card Image" class="card-img-top">
	                	</a>
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
    }

    private function handle_grid($data)
    {
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
        foreach($data as $d)
        {
            $body[] = '
                <div class="col-lg-3 col-sm-4 col-6 item">
                    <a href="' . $d[2] . '" target="_blank">
                        <img class="img-fluid image scale-on-hover" src="' . $d[3] . '">
                    </a>
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
    }

    private function handle_compact($data)
    {
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
        foreach($data as $d)
        {
            $body[] = '
              <div class="col-lg-3 col-sm-4 col-6 item zoom-on-hover">
                <a href="' . $d[2] . '" target="_blank">
                  <img class="img-fluid image" src="' . $d[3] . '">
                  <span class="description">
                    <span class="description-heading">' . $d[0] . '</span>
                    <span class="description-body">' . $d[1] . '</span>
                  </span>
                </a>
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
    }

}
