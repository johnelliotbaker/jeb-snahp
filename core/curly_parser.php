<?php
namespace jeb\snahp\core;

class curly_parser
{
    protected $allowed_directive;

	public function __construct(
	)
	{
        $this->wrapper_tag = 'snahp';
        $this->allowed_major_tags = [
            'table', 'table_autofill',
        ];
        $this->allowed_directive = ['table', 'tr', 'td', 'a', 'img', 'span'];
	}

    public function get_wrapper_pattern()
    {
        $w = $this->wrapper_tag;
        $ptn = '#{' . $w . '}(.*?){/' . $w . '}#is';
        return $ptn;
    }

    public function validate_curly_tags($html)
    {
        preg_match_all('#{([a-z]+)(?: .*)?(?<![/|/ ])}#iU', $html, $result);
        $openedtags = $result[1];   #put all closed tags into an array
        preg_match_all('#{/([a-z]+)}#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);
        $len_closed = count($closedtags);
        if ($len_closed != $len_opened) {
            return False;
        }
        $openedtags = array_reverse($openedtags);
        for ($i=0; $i < $len_opened; $i++)
        {
            if (!in_array($openedtags[$i], $closedtags))
            {
                return False;
            }
            else
            {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return True;
    }

    public function return_malformed($strn)
    {
        $res = '***Malformed snahp tag.***';
        return $res . '<br>' . $strn;
    }

    public function interpolate_curly_table($strn)
    {
        $ptn = '/{([^}]*)}/is';
        $strn = preg_replace_callback($ptn, function($m) {
            $allowed_directive = $this->allowed_directive;
            $sub = $m[1];
            $b_open = False;
            if ($sub && $sub[0] == '/')
            {
                $sub = substr($sub, 1);
            }
            else
            {
                $b_open = True;
            }
            preg_match('/(\w+)/is', $sub, $match);
            if ($match && in_array($match[0], $allowed_directive))
            {
                switch ($match[0])
                {
                case 'table':
                    if ($b_open)
                    {
                        $tag = '<div class="request_table container"><div class="request_table wrapper">';
                        $tag .= "<$m[1]>";
                    }
                    else
                    {
                        $tag = "<$m[1]>";
                        $tag .= '</div></div>';
                    }
                    return $tag;
                    break;
                default:
                    return "<$m[1]>";
                }
            }
        }, $strn);
        $strn = str_replace('<br>', '', $strn);
        return $strn;
    }

    public function interpolate_table_search_master($strn, $tag_name)
    {
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        $res = [];
        $class = ['search_master'];
        $class_strn = implode(' ', $class);
        $res[] = '<input id="searchbox_' . $uuid . '"type="search" class="' . $class_strn . '" placeholder="Omni Search"></input>';
        $res = implode(PHP_EOL, $res);
        $strn = preg_replace($ptn, $res, $strn);
        return $strn;
    }

    public function interpolate_gallery($strn, $tag_name, $type, $options=[])
    {
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $content = preg_replace("#<br>#", PHP_EOL, $content);
        $arr = explode(PHP_EOL, $content);
        foreach($arr as $entry)
        {
            if ($entry)
            {
                $entry = preg_replace('#\s+#', ' ', $entry);
                $entry = preg_replace('#`\s*#', '` ', $entry);
                $data[] = explode('` ', $entry);
            }
        }
        $gallery = new \jeb\snahp\core\template\gallery;
        $html = $gallery->handle($type, $data, $options);
        return $html;
    }

    public function interpolate_curly_table_autofill($strn, $tag_name, $b_search=false)
    {
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        $res = [];
        $class = ['autofill'];
        $class_strn = implode(' ', $class);
        if ($b_search)
        {
            $res[] = '<input id="searchbox_' . $uuid . '"type="search" class="' . $class_strn . '" placeholder="Search"></input>';
        }
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $content = preg_replace("#<br>#", PHP_EOL, $content);
        $arr = explode(PHP_EOL, $content);
        $res[] = "<div class=\"$class_strn\">";
        $res[] = "<table id=\"table_$uuid\" class=\"$class_strn\">";
        $res[] = '<tbody>';
        foreach($arr as $entry)
        {
            if ($entry)
            {
                $tmp = '';
                $entry = preg_replace('#\s+#', ' ', $entry);
                $entry = preg_replace('#`\s*#', '` ', $entry);
                $a_elem = explode('` ', $entry);
                foreach($a_elem as $elem)
                {
                    $tmp .= "<td>$elem</td>";
                }
                $res[] = "<tr>$tmp</tr>";
            }
        }
        $res[] = '</tbody></table></div>';
        $res = implode(PHP_EOL, $res);
        $strn = preg_replace($ptn, $res, $strn);
        return $strn;
    }

    public function interpolate_mega_video($strn, $tag_name)
    {
        $allowed_attr = ['style', 'class', 'src', 'align'];
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $strn = '<div align="center"><iframe src="https://mega.nz/embed#' . $content . '" width="640" height="360" frameborder="0" allowfullscreen></iframe></div>';
        return $strn;
    }

    public function interpolate_youtube($strn, $tag_name)
    {
        $allowed_attr = ['style', 'class', 'src', 'align'];
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $strn = '<div align="center"><iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/' . $content . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        return $strn;
    }

    public function interpolate_gfycat($strn, $tag_name)
    {
        $allowed_attr = ['style', 'class', 'src', 'align'];
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $strn = "<div style='position:relative; padding-bottom:calc(75.00% + 44px)'><iframe src='https://gfycat.com/ifr/" . $content . "?&hd=1' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0;' allowfullscreen></iframe></div>";
        return $strn;
    }

    public function interpolate_img($strn, $tag_name)
    {
        $allowed_attr = ['style', 'class', 'src', 'align'];
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        preg_match_all('#(\w*)="([^"]*?)"#is', $content, $matches);
        $align = 'left';
        foreach($matches[1] as $key => $attr)
        {
            if (!in_array($attr, $allowed_attr))
            {
                return $this->return_malformed($match[0]);
            }
            if ($attr=='align')
            {
                $align = $matches[2][$key];
            }
        }
        $content = preg_replace("#<br>#", PHP_EOL, $content);
        $strn = '<div align="'. $align . '"><img '. $content . '></img></div>';
        return $strn;
    }

    public function parse_snahp($strn)
    {
        $ptn = $this->get_wrapper_pattern();
        // $strn = preg_replace_callback($ptn, 'self::parse', $strn);
        $strn = preg_replace_callback($ptn, function($match){
            $content = $match[1];
            // If {snahp}{/snahp}
            if (!$content) return $match[0];
            // {snahp} must be followed by curly
            if ($content[0] != '{')
            {
                return $this->return_malformed($match[0]);
            }
            preg_match('#{([a-zA-Z_]*?)}#is', $content, $tag_type);
            if (!$tag_type || count($tag_type)<2)
            {
                return $this->return_malformed($match[0]);
            }
            $tag_type = $tag_type[1];
            switch ($tag_type)
            {
            case 'table':
                $res = $this->interpolate_curly_table($content);
                break;
            case 'table_autofill':
                $res = $this->interpolate_curly_table_autofill($content, $tag_type);
                break;
            case 'table_autofill_search':
                $res = $this->interpolate_curly_table_autofill($content, $tag_type, true);
                break;
            case 'table_search_master':
                $res = $this->interpolate_table_search_master($content, $tag_type);
                break;
            case 'img':
                $res = $this->interpolate_img($content, $tag_type);
                break;
            case 'gfycat':
                $res = $this->interpolate_gfycat($content, $tag_type);
                break;
            case 'gallery_cards':
                $res = $this->interpolate_gallery($content, $tag_type, 'cards', ['size' => 'default']);
                break;
            case 'gallery_cards_sm':
                $res = $this->interpolate_gallery($content, $tag_type, 'cards', ['size' => 'sm']);
                break;
            case 'gallery_cards_lg':
                $res = $this->interpolate_gallery($content, $tag_type, 'cards', ['size' => 'lg']);
                break;
            case 'gallery_grid':
                $res = $this->interpolate_gallery($content, $tag_type, 'grid', ['size' => 'default']);
                break;
            case 'gallery_grid_sm':
                $res = $this->interpolate_gallery($content, $tag_type, 'grid', ['size' => 'sm']);
                break;
            case 'gallery_grid_lg':
                $res = $this->interpolate_gallery($content, $tag_type, 'grid', ['size' => 'lg']);
                break;
            case 'gallery_compact':
                $res = $this->interpolate_gallery($content, $tag_type, 'compact', ['size' => 'default']);
                break;
            case 'gallery_compact_sm':
                $res = $this->interpolate_gallery($content, $tag_type, 'compact', ['size' => 'sm']);
                break;
            case 'gallery_compact_lg':
                $res = $this->interpolate_gallery($content, $tag_type, 'compact', ['size' => 'lg']);
                break;
            case 'youtube':
                $res = $this->interpolate_youtube($content, $tag_type);
                break;
            case 'mv':
                $res = $this->interpolate_mega_video($content, $tag_type);
                break;
            default:
                $res = 'default';
                break;
            }
            return $res;
        }, $strn);
        return $strn;
    }

    public function parse($strn)
    {
        $ptn = '#.*#';
        $strn = preg_replace_callback($ptn, 'self::callback', $strn);
        return $strn;
    }
}
