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
        if (!$data)
        {
            return '';
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

    public function interpolate_fulfill($strn, $tag_name)
    {
        global $db, $request, $user, $phpbb_container;
        $def = $phpbb_container->getParameter('jeb.snahp.req')['def'];
        $allowed_attr = ['style', 'class', 'src', 'align'];
        $uuid = uniqid();
        $ptn = '#{' . $tag_name . '}(.*?){/'. $tag_name . '}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $topic_id = $request->variable('t', '0');
        $request_data = $this->select_request($topic_id);
        if (!$request_data)
        {
            return '#fulfill#';
        }
        $topic_id = $request_data['tid'];
        $forum_id = $request_data['fid'];
        $post_id = $request_data['pid'];
        $user_id = $user->data['user_id'];
        $requester_id = $request_data['requester_uid'];
        $fulfiller_id = $request_data['fulfiller_uid'];
        if (!in_array($user_id, [$requester_id, $fulfiller_id]))
        {
            return '';
        }
        if (in_array($request_data['status'], $def['set']['closed']))
        {
            return '';
        }
        $u_solve = "/app.php/snahp/reqs/{$forum_id}/{$topic_id}/{$post_id}/2/";
        $u_terminate = "/app.php/snahp/reqs/{$forum_id}/{$topic_id}/{$post_id}/9/";
        $strn = '
<div class=twbs>
<a href="'. $u_solve . '" title="Mark Solved" class="button-icon-only">
    <i class="icon fa-check-square fa-fw" style="color:#383" aria-hidden="true"></i>
    <span class="">Click if you are satisfied with the fulfillment</span>
</a><br>
<a href="'. $u_terminate .'" title="Mark Solved" class="button-icon-only">
    <i class="icon fa-trash fa-fw" style="color:#660000" aria-hidden="true"></i>
    <span class="">Click if the fulfillment did not meet your request specifications</span>
</a>
            
</div>';
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
        // $p = '#<code>(.*?)<\/code>#s';
        // preg_match_all($p, $strn, $codebox_matches);
        // $uuid = uniqid('codebox_');
        // $ptn_uuid = '#' . $uuid . '#s';
        // $strn = preg_replace($p, $uuid, $strn);
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
            switch ($tag_type)/*{{{*/
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
            case 'fulfill':
                $res = $this->interpolate_fulfill($content, $tag_type);
                break;
            default:
                $res = 'default';
                break;
            }/*}}}*/
            return $res;
        }, $strn);
        // $start = 0;
        // $res = [];
        // $n = strlen($strn);
        // $i = 0;
        // $new_strn = '';
        // while (preg_match($ptn_uuid, $strn, $matches, PREG_OFFSET_CAPTURE, $start))
        // {
        //     $width = strlen($matches[0][0]);
        //     $cursor = (int) $matches[0][1];
        //     $new_strn .= substr($strn, $start, $cursor-$start);
        //     $new_strn .= $codebox_matches[0][$i];
        //     $i += 1;
        //     $start = $cursor + $width;
        // };
        // if ($start < $n)
        // {
        //     $new_strn .= substr($strn, $start);
        // }
        // return $new_strn;
        return $strn;
    }

    public function parse($strn)
    {
        $ptn = '#.*#';
        $strn = preg_replace_callback($ptn, 'self::callback', $strn);
        return $strn;
    }

    public function is_request($forum_id)/*{{{*/
    {
        global $config;
        $cache_time = 5;
        $fid_requests = $config['snp_fid_requests'];
        $sub = $this->select_subforum($fid_requests, $cache_time);
        $res = in_array($forum_id, $sub) ? true : false;
        return $res;
    }/*}}}*/

    private function select_subforum($parent_id, $cooldown=0, $b_immediate=false)/*{{{*/
    {
        global $db;
        $sql = 'SELECT left_id, right_id FROM ' . FORUMS_TABLE . ' WHERE forum_id=' . $parent_id;
        $result = $db->sql_query($sql, $cooldown);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $parent_left_id = $row['left_id'];
        $parent_right_id = $row['right_id'];
        $sql = 'SELECT forum_id FROM ' . FORUMS_TABLE . ' WHERE parent_id = ' . $parent_id . ' OR (left_id BETWEEN ' . $parent_left_id . ' AND ' . $parent_right_id . ')';
        if ($b_immediate==true)
        {
            $sql .= ' AND parent_id=' . $parent_id;
        }
        $result = $db->sql_query($sql, $cooldown);
        $data = array_map(function($array){return $array['forum_id'];}, $db->sql_fetchrowset($result));
        $db->sql_freeresult($result);
        return $data;
    }/*}}}*/

    private function select_request($tid)/*{{{*/
    {
        global $phpbb_container, $db;
        $tbl = $phpbb_container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['req'] . " WHERE tid=$tid";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }/*}}}*/

}
