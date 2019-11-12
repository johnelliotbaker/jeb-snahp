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
        $html = '
<span 
onClick="ImgCompare.setup_modal(' . "'$url1', '$url2', '$label1', '$label2'" . ')"
	class="imgcompare_thumbnail_container pointer noselect">
    <img src="'. $url1 .'" class="imgcompare_thumbnail">
    <span class="crop_bottom">
        <img src="'. $url2 .'" class="imgcompare_thumbnail">
    </span>
</span>
';
        return $html;
    }/*}}}*/

}
