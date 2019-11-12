<?php
namespace jeb\snahp\core;

class mediainfo
{
    protected $general_category;
    protected $video_category;
    protected $audio_category;
    protected $subtitle_category;
    protected $allowed_category;
    protected $data;
    protected $language_lut;

	public function __construct(/*{{{*/
	)
	{
        $this->general_category = ['General'];
        $this->video_category = ['Video', 'Video #1', 'Video #2', 'Video #3', 'Video #4', 'Video #5', 'Video #6', 'Video #7', 'Video #8', 'Video #9'];
        $this->audio_category = ['Audio', 'Audio #1', 'Audio #2', 'Audio #3', 'Audio #4', 'Audio #5', 'Audio #6', 'Audio #7', 'Audio #8', 'Audio #9'];
        $this->subtitle_category = ['Text', 'Text #1', 'Text #2', 'Text #3', 'Text #4', 'Text #5', 'Text #6', 'Text #7', 'Text #8', 'Text #9'];
        $this->allowed_category = array_merge(
            $this->general_category,
            $this->video_category,
            $this->audio_category,
            $this->subtitle_category
        );
        $this->data = [];
        $this->language_lut = [
            'english'   => 'gb',
            'korean'    => 'kr',
            'japanese'  => 'jp',
            'chinese'   => 'cn',
            'french'    => 'fr',
            'spanish'   => 'es',
            'russian'   => 'ru',
            'german'    => 'de',
            'italian'   => 'it',
            'thai'      => 'th',
            'malaysian' => 'my',
            'swedish'   => 'se',
            // 'danish'    => 'dk',
            'czech'     => 'cz',
            'dutch'     => 'nl',
            'finnish'   => 'fi',
            'portuguese' => 'pt',
        ];
	}/*}}}*/

    private function normalize_newline($strn)/*{{{*/
    {
        $strn = preg_replace('#<br>#s', '', $strn);
        return $strn;
    }/*}}}*/

    private function string2dict($strn)/*{{{*/
    {
        $arr = explode("\n", $strn);
        $allowed_category = $this->allowed_category;
        $aggro = [];
        $b_processing = false;
        foreach ($arr as $line)
        {
            if (!$line) continue;
            if (strpos($line, ':') == false)
            {
                if (in_array($line, $allowed_category))
                {
                    $b_processing = true;
                    $major = $line;
                    if (array_key_exists($major, $aggro))
                    {
                        return false;
                    }
                    $aggro[$major] = [];
                }
                else
                {
                    $b_processing = false;
                }
            }
            else
            {
                if ($b_processing)
                {
                    $tmp = array_map('trim', explode(':', $line));
                    $n = count($tmp);
                    // Special: display aspect has a ':' in value
                    if ($n == 3 && $tmp[0] == 'Display aspect ratio')
                    {
                        $aggro[$major][$tmp[0]] = implode(':', array_slice($tmp, 1));
                    }
                    else if (count($tmp) == 2)
                    {
                        $tmp = array_map('trim', $tmp);
                        $aggro[$major][$tmp[0]] = $tmp[1];
                    }
                }
            }
        }
        return $aggro;
    }/*}}}*/

    private function collect_key_info($type, $key='Language')/*{{{*/
    {
        $collection = $this->data;
        switch ($type)
        {
        case 'audio':
            $a_category = $this->audio_category;
            break;
        case 'subtitle':
            $a_category = $this->subtitle_category;
            break;
        default:
            return [];
        }
        $a_aggro = [];
        foreach ($a_category as $major)
        {
            if (isset($collection[$major]))
            {
                $data = $collection[$major];
                $a_aggro[] = isset($data[$key]) ? $data[$key] : '';
            }
        }
        $a_aggro = array_unique($a_aggro);
        if (!$a_aggro) return [];
        $res = [];
        $i = 1;
        $key_capital = ucfirst($key);
        foreach ($a_aggro as $val)
        {
            if ($val)
            {
                $res["${key_capital} ${i}"] = $val;
                $i += 1;
            }
        }
        return $res;
    }/*}}}*/

    private function get_val_or_null($keyword, $data)/*{{{*/
    {
        return array_key_exists($keyword, $data) ? $data[$keyword] : null;
    }/*}}}*/

    private function get_val_or_unknown($keyword, $data)/*{{{*/
    {
        return array_key_exists($keyword, $data) ? $data[$keyword] : '?';
    }/*}}}*/

    private function get_general_filesize($data)/*{{{*/
    {
        return $this->get_val_or_null('File size', $data);
    }/*}}}*/

    private function get_general_duration($data)/*{{{*/
    {
        return $this->get_val_or_null('Duration', $data);
    }/*}}}*/

    private function get_general_bitrate($data)/*{{{*/
    {
        $strn = $this->get_val_or_null('Overall bit rate', $data);
        return $this->format_bitrate($strn);
    }/*}}}*/

    private function get_general_format($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
    }/*}}}*/

    private function generate_general_content($data, $extra = [])/*{{{*/
    {
        $res = [];
        $function_prefix = 'get_general_';
        $data = $data['General'];
        $a_element = [
            ['f' => 'filesize' , 'alias' => 'File size'],
            ['f' => 'duration' , 'alias' => 'Duration'],
            ['f' => 'bitrate'  , 'alias' => 'Bit rate'],
            ['f' => 'format'   , 'alias' => 'Format'],
        ];
        foreach ($a_element as $element)
        {
            $entry['type'] = 'kv';
            $entry['content'] = $this->{$function_prefix . $element['f']}($data);
            $res[$element['alias']] = $entry;
        }
        return array_merge($res, $extra);
    }/*}}}*/

    private function get_video_format($data)/*{{{*/
    {
        $format = $this->get_val_or_null('Format', $data);
        return $format ? $format : '?';
    }/*}}}*/

    private function format_bitrate($strn)/*{{{*/
    {
        return preg_replace('#(\d+)\s?(\d+)#', '\1\2', $strn);
    }/*}}}*/

    private function get_video_vres($data)/*{{{*/
    {
        $width = $this->get_val_or_null('Width', $data);
        $width = preg_replace('#(\d+)[\.\s]?(\d+)(.*)#s', '\1\2', $width);
        $height = $this->get_val_or_null('Height', $data);
        $height = preg_replace('#(\d+)[\.\s]?(\d+)(.*)#s', '\1\2', $height);
        $ar = $this->get_val_or_null('Display aspect ratio', $data);
        $dimensions = $this->join_or_first($width, $height, 'x');
        $res = $this->join_or_first($dimensions, $ar);
        return $res ? $res : '?';
    }/*}}}*/

    private function get_video_framerate($data)/*{{{*/
    {
        $v = $this->get_val_or_null('Frame rate', $data);
        $v = preg_replace('#(\d+\.?\d+)(.*)#s', '\1 fps', $v);
        return $v ? $v : '?';
    }/*}}}*/

    private function get_video_bitrate($data)/*{{{*/
    {
        $strn = $this->get_val_or_unknown('Bit rate', $data);
        return $this->format_bitrate($strn);
    }/*}}}*/

    private function get_video_bitdepth($data)/*{{{*/
    {
        return $this->get_val_or_null('Bit depth', $data);
    }/*}}}*/

    private function get_video_format_with_bitdepth($data)/*{{{*/
    {
        $format = $this->get_video_format($data);
        $bitdepth = $this->get_video_bitdepth($data);
        return $this->join_or_first($format, $bitdepth);
    }/*}}}*/

    private function generate_video_content($data, $extra = [])/*{{{*/
    {
        $cfg['css_prefix'] = 'video_';
        $res = [];
        $function_prefix = 'get_video_';
        $a_data = $this->aggregate_video_data($data);
        $a_element = [
            ['f' => 'format_with_bitdepth',    'alias' => 'Format',],
            ['f' => 'vres',      'alias' => 'Dimensions',],
            ['f' => 'framerate', 'alias' => 'Frame rate',],
            ['f' => 'bitrate',   'alias' => 'Bit rate',],
        ];
        foreach ($a_data as $data)
        {
            $tmp = [];
            foreach ($a_element as $element)
            {
                $entry['type'] = 'kv';
                $entry['content'] = $this->{$function_prefix . $element['f']}($data);
                $tmp[$element['alias']] = $entry;
            }
            $res[] = $tmp;
        }
        $n_video = count($res);
        if ($n_video < 1) { return $res; }
        if ($n_video == 1)
        {
            $html = $this->auto_convert_to_html($res[0], $cfg);
            $res = [];
            $res['Video']['type'] = 'fullwidth';
            $res['Video']['content'] = $html;
            return array_merge($res, $extra);
        }
        $tmp = [];
        $html = $this->auto_convert_to_html($res[0], $cfg);
        $tmp['Video']['type'] = 'fullwidth';
        $tmp['Video']['content'] = $html;
        for ($i=1; $i < $n_video; $i++)
        {
            $a = $this->auto_convert_to_html($res[$i], $cfg);
            $video_name = 'Video ' . (string) ($i+1);
            $b = [];
            $b[$video_name]['type'] = 'fullwidth';
            $b[$video_name]['content'] = $a;
            $tmp[$video_name] = $this->make_collapsable($b, $video_name);
        }
        return array_merge($tmp, $extra);
    }/*}}}*/

    private function aggregate_video_data($a_data)/*{{{*/
    {
        $res = [];
        $allowed = $this->video_category;
        foreach($allowed as $major)
        {
            if (array_key_exists($major, $a_data))
            {
                $res[] = $a_data[$major];
            }
        }
        return $res;
    }/*}}}*/

    private function aggregate_text_data($a_data)/*{{{*/
    {
        $res = [];
        $allowed = $this->subtitle_category;
        foreach($allowed as $major)
        {
            if (array_key_exists($major, $a_data))
            {
                $res[] = $a_data[$major];
            }
        }
        return $res;
    }/*}}}*/

    private function get_text_format($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
    }/*}}}*/

    private function get_text_subtitle($data)/*{{{*/
    {
        return $this->get_val_or_null('Language', $data);
    }/*}}}*/

    private function generate_subtitle_content($data, $extra = [])/*{{{*/
    {
        $res = [];
        $function_prefix = 'get_text_';
        $a_data = $this->aggregate_text_data($data);
        $a_element = [
            ['f' => 'subtitle' , 'alias' => 'Language'],
            ['f' => 'format' , 'alias' => 'Format'],
        ];
        $i = 1;
        foreach ($a_data as $data)
        {
            $tmp = ['id' => $i];
            foreach ($a_element as $element)
            {
                $tmp[$element['alias']] = $this->{$function_prefix . $element['f']}($data);
            }
            $entry['type'] = 'fullwidth';
            $language =  $this->get_language_image_or_strn($tmp['Language'] , ['append_text' => true])['value'];
            $format = $tmp['Format'];
            $html = '<div class="row"><div class="col-auto subtitle_key">#' . $i .  ':</div><div class="float-right subtitle_value col">' . $language . " ${format}</div></div>";
            $entry['content'] =  $html;
            $res["Subtitle ${tmp['id']}"] = $entry;
            $i += 1;
        }
        $tmp = [];
        if ($res)
        {
            $tmp['Subtitle'] = $this->make_collapsable($res, 'Subtitles');
        }
        return array_merge($tmp, $extra);
    }/*}}}*/

    private function aggregate_audio_data($a_data)/*{{{*/
    {
        $res = [];
        $allowed = $this->audio_category;
        foreach($allowed as $major)
        {
            if (array_key_exists($major, $a_data))
            {
                $res[] = $a_data[$major];
            }
        }
        return $res;
    }/*}}}*/

    private function get_audio_format_profile($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
    }/*}}}*/

    private function get_audio_commercial_name($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
    }/*}}}*/

    private function get_audio_format($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
    }/*}}}*/

    private function get_audio_title($data)/*{{{*/
    {
        return $this->get_val_or_null('Title', $data);
    }/*}}}*/

    private function get_audio_language($data)/*{{{*/
    {
        return $this->get_val_or_null('Language', $data);
    }/*}}}*/

    private function get_audio_bitrate($data)/*{{{*/
    {
        $strn = $this->get_val_or_null('Bit rate', $data);
        return $this->format_bitrate($strn);
    }/*}}}*/

    private function get_audio_channels($data)/*{{{*/
    {
        $b = preg_match('#(\d+).*#is', $this->get_val_or_null('Channel(s)', $data), $match);
        if (!$b) { return ''; }
        $ch = (string) $match[1];
        switch ($ch)
        {
        case '2':
            return '2.0ch';
        case '6':
            return '5.1ch';
        case '8':
            return '7.1ch';
        default:
        }
        return '';
    }/*}}}*/

    private function get_country_code_from_language($strn)/*{{{*/
    {
        if (!$strn) return null;
        $strn = strtolower($strn);
        if (array_key_exists($strn, $this->language_lut))
        {
            return $this->language_lut[$strn];
        }
        return null;
    }/*}}}*/

    private function get_language_image_or_strn($strn, $options=[])/*{{{*/
    {
        $country_code = $this->get_country_code_from_language($strn);
        if (!$country_code)
        {
            return ['type' => 'string', 'value' => $strn];
        }
        $img_html = '<img class="flag" src="/ext/jeb/snahp/styles/all/template/flags/4x3/' . $country_code . '.svg" title="' . $strn . '"></img>';
        if (isset($options['append_text']) && $options['append_text'])
        {
            $img_html .= " $strn";
        }
        return ['type' => 'html', 'value' => $img_html];
    }/*}}}*/

    private function join_or_first($first, $second, $delimiter=' @ ')/*{{{*/
    {
        if ($second)
        {
            return join($delimiter, [$first, $second]);
        }
        return $first;
    }/*}}}*/
    
    private function generate_audio_content($data, $extra = [])/*{{{*/
    {
        $max_audio_entry = 4;
        // How many are listed int he audio column without creating the
        // "Additional Audio" collapsable widget
        $res = [];
        $function_prefix = 'get_audio_';
        $a_data = $this->aggregate_audio_data($data);
        $a_element = [
            ['f' => 'language' , 'alias' => 'Language'],
            ['f' => 'format' , 'alias' => 'Format'],
            ['f' => 'channels' , 'alias' => 'Channels'],
            ['f' => 'bitrate' , 'alias' => 'Bit rate'],
        ];
        $i = 1;
        foreach ($a_data as $data)
        {
            $tmp = [];
            $separator = '|';
            foreach ($a_element as $element)
            {
                $tmp[$element['alias']] = $this->{$function_prefix . $element['f']}($data);
            }
            $language = '';
            if ($tmp['Language'])
            {
                $lang = $this->get_language_image_or_strn($tmp['Language']);
                if ($lang['type'] == 'html')
                {
                    $language = $lang['value'];
                }
                else
                {
                    $language = "${lang['value']} ${separator}";
                }
            }
            if ($tmp['Channels']) $t[] = $tmp['Channels'];
            $format = $tmp['Format'];
            $bitrate = $tmp['Bit rate'];
            $specs = $this->join_or_first($format, $bitrate);
            $entry['type'] = 'fullwidth';
            $value = "$language $specs";
            $html = '<div class="row"><div class="col-auto audio_key">#' . $i .  ':</div><div class="float-right audio_value col">' . $value . '</div></div>';
            $entry['content'] = $html;
            $res[] = $entry;
            $i += 1;
        }
        $a_remain = array_slice($res, $max_audio_entry);
        $res = array_slice($res, 0, $max_audio_entry);
        if (isset($a_remain) && $a_remain)
        {
            $res[] = $this->make_collapsable($a_remain, 'Additional Audio');
        }
        return array_merge($res, $extra);
    }/*}}}*/

    private function validate_data($data)/*{{{*/
    {
        if (!array_key_exists('General', $this->data)) return false;
        if (!(array_key_exists('Video', $this->data) || array_key_exists('Video #1', $this->data))) return false;
        if (!(array_key_exists('Audio', $this->data) || array_key_exists('Audio #1', $this->data))) return false;
        return true;
    }/*}}}*/

    public function make_mediainfo($strn)/*{{{*/
    {
        $strn = $this->normalize_newline($strn);
        $original = trim($strn);
        $this->data = $this->string2dict($strn);
        if ($this->data === false) { return ''; }
        if (!$this->validate_data($this->data)) return '';
        $subtitle = $this->generate_subtitle_content($this->data);
        $res[] = '';
        $res[] = '<div class="twbs mediainfo"><div class="container-fluid"><div class="row">';
        $res[] = '<div class="col-12 col-md-4 general">';
        $res[] = '<div class="col-12 title">General</div>';
        $res[] = $this->make_bucket($this->generate_general_content($this->data, $subtitle), ['css_prefix' => 'general_']);
        $res[] = '</div>';
        $res[] = '<div class="col-12 col-md-4 video">';
        $res[] = '<div class="col-12 title">Video</div>';
        $res[] = $this->make_bucket($this->generate_video_content($this->data));
        $res[] = '</div>';
        $res[] = '<div class="col-12 col-md-4 audio">';
        $res[] = '<div class="col-12 title">Audio</div>';
        $res[] = $this->make_bucket($this->generate_audio_content($this->data));
        $res[] = '</div>';
        $res[] = "</div></div></div>";
        $res = join('', $res);
        $res .= '<div class="codebox" style="margin-top:0px; box-shadow: none; margin-left: 0px; margin-right: 0px;"><p style="border-bottom: none;">Code: <a href="#" onclick="selectCode(this); return false;">Select all</a></p><pre><code>' . $original . '</code></pre></div>';
        return $res;
    }/*}}}*/

    private function get_uuid_strn()/*{{{*/
    {
        return uniqid('mediainfo_collapsable_');
    }/*}}}*/

    private function make_collapsable($a_data, $title='')/*{{{*/
    {
        $uuid = $this->get_uuid_strn();
        $html = $this->auto_convert_to_html($a_data);
        $html = '<div class="row collapse_handle pointer noselect" data-toggle="collapse" href="#' . $uuid . '" role="button" aria-expanded="false" aria-controls="' . $uuid . '"><i class="icon fa-plus-square-o collapse_plus" aria-hidden="true"></i><div class="ml-1">' . $title . '</div></div>
      <div class="row"> <div class="col"> <div class="collapse" id="' . $uuid . '">
      <div class="card card-body collapsable">' . $html . '</div>
      </div></div></div>';
        $entry['type'] = 'fullwidth';
        $entry['content'] = $html;
        return $entry;
    }/*}}}*/

    private function auto_convert_to_html($a_data, $cfg=[])/*{{{*/
    {
        $res = [];
        foreach ($a_data as $k=>$v)
        {
            $type = $v['type'];
            switch ($type)
            {
            case 'fullwidth':
                $res[] = $this->convert_to_fullwidth_html($v['content'], $cfg);
                break;
            case 'kv':
                $res[$k] = $this->convert_to_kv_html($k, $v['content'], $cfg);
                break;
            }
        }
        return join("\n", $res);
    }/*}}}*/

    private function convert_to_fullwidth_html($v, $cfg=[])/*{{{*/
    {
        $res[] = "<div class='row'>";
        $res[] = "<div class='col fullwidth'>${v}</div>";
        $res[] = "</div>";
        return join("\n", $res);
    }/*}}}*/

    private function convert_to_kv_html($k, $v, $cfg=[])/*{{{*/
    {
        $css_prefix = array_key_exists('css_prefix', $cfg) ? $cfg['css_prefix'] : '';
        $k = "<div class='row m-0 p-0'><div class='${css_prefix}key col'>${k}:</div>";
        $v = "<div class='${css_prefix}value col'>${v}</div>";
        $res[] = "<div class='row'><div class='col-12 p-0 m-0'>";
        $res[] = $k . $v;
        $res[] = "</div></div></div>";
        return join("\n", $res);
    }/*}}}*/

    private function make_bucket($data, $cfg=[])/*{{{*/
    {
        $temp = '<p class="m-0 p-0"><i class="icon fa-plus-square-o pointer noselect" aria-hidden="true" data-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1">Subtitles</i> </p>
      <div class="row"> <div class="col"> <div class="collapse" id="multiCollapseExample1">
      <div class="card card-body"> Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. </div>
      </div></div></div>';
        $i = 0;
        $res = [];
        foreach($data as $k=>$v)
        {
            switch($v['type'])
            {
            case 'kv':
                $res[] = $this->convert_to_kv_html($k, $v['content'], $cfg);
                break;
            case 'fullwidth':
                $res[] = $this->convert_to_fullwidth_html($v['content'], $cfg);
                break;
            }
        }
        return join("\n", $res);
    }/*}}}*/

}
