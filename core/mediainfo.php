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

	public function __construct(/*{{{*/
	)
	{
        $this->general_category = ['General'];
        $this->video_category = ['Video'];
        $this->audio_category = ['Audio', 'Audio #1', 'Audio #2'];
        $this->subtitle_category = ['Text', 'Text #1', 'Text #2'];
        $this->allowed_category = array_merge(
            $this->general_category,
            $this->video_category,
            $this->audio_category,
            $this->subtitle_category
        );
        $this->data = [];
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

    private function make_bucket($data, $column=2)/*{{{*/
    {
        foreach($data as $k=>$v)
        {
            if ($column==2)
            {
                $k = "<div class='col-6 float-left key'>${k}:</div>";
                $v = "<div class='col-6 float-right value'>${v}</div>";
                $res[] = "<div class='row'><div class='col-12'>";
                $res[] = $k . $v;
                $res[] = "</div></div>";
            }
            else
            {
                $v = "<div class='col-12 float-right value'>${v}</div>";
                $res[] = "<div class='row'><div class='col-12'>";
                $res[] = $v;
                $res[] = "</div></div>";
            }
        }
        return join("\n", $res);
    }/*}}}*/

    private function get_val_or_null($keyword, $data)/*{{{*/
    {
        return array_key_exists($keyword, $data) ? $data[$keyword] : null;
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
        return $this->get_val_or_null('Overall bit rate', $data);
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
            $res[$element['alias']] = $this->{$function_prefix . $element['f']}($data);
        }
        return array_merge($res, $extra);
    }/*}}}*/

    private function get_video_format($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
    }/*}}}*/

    private function format_bitrate($strn)/*{{{*/
    {
        return preg_replace('#(\d+\.?)[\s]?(\d+)(.*)#s', '\1\2\3', $strn);
    }/*}}}*/

    private function get_video_vres($data)/*{{{*/
    {
        $width = $this->get_val_or_null('Width', $data);
        $width = preg_replace('#(\d+)[\.\s]?(\d+)(.*)#s', '\1\2', $width);
        $height = $this->get_val_or_null('Height', $data);
        $height = preg_replace('#(\d+)[\.\s]?(\d+)(.*)#s', '\1\2', $height);
        $ar = $this->get_val_or_null('Display aspect ratio', $data);
        return "${width} x ${height} @ ${ar}";
    }/*}}}*/

    private function get_video_framerate($data)/*{{{*/
    {
        $v = $this->get_val_or_null('Frame rate', $data);
        return preg_replace('#(\d+\.?\d+)(.*)#s', '\1', $v);
    }/*}}}*/

    private function get_video_bitrate($data)/*{{{*/
    {
        $strn = $this->get_val_or_null('Bit rate', $data);
        return $this->format_bitrate($strn);
    }/*}}}*/

    private function generate_video_content($data, $extra = [])/*{{{*/
    {
        $res = [];
        $function_prefix = 'get_video_';
        $data = $data['Video'];
        $a_element = [
            ['f' => 'format' , 'alias' => 'Format'],
            ['f' => 'vres' , 'alias' => 'Dimensions'],
            ['f' => 'framerate' , 'alias' => 'Frame rate'],
            ['f' => 'bitrate' , 'alias' => 'Bit rate'],
        ];
        foreach ($a_element as $element)
        {
            $res[$element['alias']] = $this->{$function_prefix . $element['f']}($data);
        }
        return array_merge($res, $extra);
    }/*}}}*/

    private function aggregate_text_data($a_data)/*{{{*/
    {
        $res = [];
        $allowed = ['Text', 'Text #1', 'Text #2', 'Text #3', 'Text #4'];
        foreach($allowed as $major)
        {
            if (array_key_exists($major, $a_data))
            {
                $res[] = $a_data[$major];
            }
        }
        return $res;
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
        ];
        $i = 1;
        foreach ($a_data as $data)
        {
            $tmp = ['id' => $i];
            foreach ($a_element as $element)
            {
                $tmp[$element['alias']] = $this->{$function_prefix . $element['f']}($data);
            }
            $res["Subtitle ${tmp['id']}"] = "${tmp['Language']}";
            $i += 1;
        }
        return array_merge($res, $extra);
    }/*}}}*/

    private function aggregate_audio_data($a_data)/*{{{*/
    {
        $res = [];
        $allowed = ['Audio', 'Audio #1', 'Audio #2', 'Audio #3', 'Audio #4'];
        foreach($allowed as $major)
        {
            if (array_key_exists($major, $a_data))
            {
                $res[] = $a_data[$major];
            }
        }
        return $res;
    }/*}}}*/

    private function get_audio_format($data)/*{{{*/
    {
        return $this->get_val_or_null('Format', $data);
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

    private function generate_audio_content($data, $extra = [])/*{{{*/
    {
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
            $tmp = ['id' => $i];
            foreach ($a_element as $element)
            {
                $tmp[$element['alias']] = $this->{$function_prefix . $element['f']}($data);
            }
            $res[] = "#${tmp['id']}:&nbsp; ${tmp['Language']} | ${tmp['Channels']} | ${tmp['Format']} @ ${tmp['Bit rate']}";
            $i += 1;
        }
        return array_merge($res, $extra);
    }/*}}}*/

    public function make_mediainfo($strn)/*{{{*/
    {
        $strn = $this->normalize_newline($strn);
        $original = trim($strn);
        $this->data = $this->string2dict($strn);
        if ($this->data === false) { return ''; }
        $subtitle = $this->generate_subtitle_content($this->data);
        $res[] = '';
        $res[] = '<div class="twbs mediainfo"><div class="container-fluid"><div class="row">';
        $res[] = '<div class="col-12 col-md-4 general">';
        $res[] = '<div class="col-12 col-md-12 title">General</div>';
        $res[] = $this->make_bucket($this->generate_general_content($this->data, $subtitle));
        $res[] = '</div>';
        $res[] = '<div class="col-12 col-md-4 video">';
        $res[] = '<div class="col-12 col-md-12 title">Video</div>';
        $res[] = $this->make_bucket($this->generate_video_content($this->data));
        $res[] = '</div>';
        $res[] = '<div class="col-12 col-md-4 audio">';
        $res[] = '<div class="col-12 col-md-12 title">Audio</div>';
        $res[] = $this->make_bucket($this->generate_audio_content($this->data), $column=1);
        $res[] = '</div>';
        $res[] = "</div></div></div>";
        $res = join('', $res);
        $res .= '<div class="codebox" style="margin-top:0px; box-shadow: none; margin-left: 0px; margin-right: 0px;"><p style="border-bottom: none;">Code: <a href="#" onclick="selectCode(this); return false;">Select all</a></p><pre style="height:0px;"><code>' . $original . '</code></pre></div>';
        return $res;
    }/*}}}*/

}
