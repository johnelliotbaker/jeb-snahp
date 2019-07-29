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
        $strn = preg_replace('#<br>#s', "\n", $strn);
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

    private function collect_audio_info()/*{{{*/
    {
        $collection = $this->data;
        $a_audio = [];
        foreach ($this->audio_category as $major)
        {
            if (isset($collection[$major]))
            {
                $data = $collection[$major];
                $a_audio[] = isset($data['Language']) ? $data['Language'] : '';
            }
        }
        $a_audio = array_unique($a_audio);
        if (!$a_audio) return [];
        $res = [];
        $i = 1;
        foreach ($a_audio as $audio)
        {
            $res["Language ${i}"] = $audio;
            $i += 1;
        }
        return $res;
    }/*}}}*/

    private function collect_subtitle_info()/*{{{*/
    {
        $collection = $this->data;
        $a_language = [];
        foreach ($this->subtitle_category as $major)
        {
            if (isset($collection[$major]))
            {
                $data = $collection[$major];
                $a_language[] = isset($data['Language']) ? $data['Language'] : '';
            }
        }
        $a_language = array_unique($a_language);
        if (!$a_language) return [];
        $res = [];
        $i = 1;
        foreach ($a_language as $language)
        {
            $res["Subtitle ${i}"] = $language;
            $i += 1;
        }
        return $res;
    }/*}}}*/

    private function make_bucket($type, $extra=[])/*{{{*/
    {
        $data = $this->data;
        if (!array_key_exists($type, $data))
        {
            return '';
        }
        $data = $data[$type];
        switch ($type)
        {
        case 'General':
            $a_terms = ['File size', 'Duration', 'Overall bit rate', 'Format'];
            break;
        case 'Video':
            $a_terms = ['Format', 'Width', 'Height', 'Display aspect ratio', 'Frame rate'];
            break;
        case 'Audio':
        case 'Audio #1':
            $a_terms = ['Format', 'Channel(s)']; # Language is collected using collection_audio_info
            break;
        default:
            return '';
        }
        $res = [];
        foreach ($extra as $k => $v)
        {
            $a_terms[] = $k;
            $data[$k] = $v;
        }
        foreach($a_terms as $term)
        {
            if (array_key_exists($term, $data))
            {
                $v = substr($data[$term], 0, 15);
                switch ($term)
                {
                case 'Overall bit rate':
                    $term = 'Bit rate';
                    break;
                case 'Display aspect ratio':
                    $term = 'Aspect ratio';
                    break;
                case 'Channel(s)':
                    $term = 'Channels';
                    break;
                case 'Frame rate':
                case 'Width':
                case 'Height':
                    $v = preg_replace('#(\d+\.?\d+)(.*)#s', '\1', $v);
                    break;
                case 'Writing library':
                    $term = 'Library';
                    break;
                }
                $v = preg_replace('#(\d+)\s*(\d+)#', '\1\2', $v);
                $v = "<div class='col-6 float-right value'>${v}</div>";
                $res[] = "<div class='row'><div class='col-12'>";
                $k = "<div class='col-6 float-left key'>${term}:</div>";
                $res[] = $k . $v;
                $res[] = "</div></div>";
            }
        }
        return join("\n", $res);
    }/*}}}*/

    public function make_mediainfo($strn)/*{{{*/
    {
        $strn = $this->normalize_newline($strn);
        $original = trim($strn);
        $b_success = $this->data = $this->string2dict($strn);
        if ($b_success === false) { return ''; }
        // To be used when making general bucket
        $subtitle_data = $this->collect_subtitle_info();
        $audio_data = $this->collect_audio_info();
        $res[] = '';
        $res[] = '<div class="twbs mediainfo"><div class="container-fluid"><div class="row">';
        $res[] = '<div class="col-12 col-md-4 general">';
        $res[] = '<div class="col-12 col-md-12 title">General</div>';
        $general = $this->make_bucket('General', $subtitle_data);
        $res[] = $general;
        $res[] = '</div>';
        $res[] = '<div class="col-12 col-md-4 video">';
        $res[] = '<div class="col-12 col-md-12 title">Video</div>';
        $video = $this->make_bucket('Video');
        $res[] = $video;
        $res[] = '</div>';
        $res[] = '<div class="col-12 col-md-4 audio">';
        $res[] = '<div class="col-12 col-md-12 title">Audio</div>';
        $audio = $this->make_bucket('Audio', $audio_data);
        if (!$audio)
        {
            $audio = $this->make_bucket('Audio #1', $audio_data);
        }
        $res[] = $audio;
        $res[] = '</div>';
        $res[] = "</div></div></div>";
        $res = join('', $res);
        // $original = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $original);
        // TODO: Find a way to do this without magic number
		$original = preg_replace("/(^[\r\n]{3,10}|[\r\n]{3,10})[\s\t]*[\r\n]+/", "c610fz545e", $original);
        $original = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $original);
        $original = preg_replace("/c610fz545e/", "\n\n", $original);
        // $original = preg_replace("/(^[\r\n]|[\r\n]+)/", "\n", $original);
        $res .= '<div class="codebox" style="margin-top:0px; box-shadow: none; margin-left: 0px; margin-right: 0px;"><p style="border-bottom: none;">Code: <a href="#" onclick="selectCode(this); return false;">Select all</a></p><pre style="height:0px;"><code>' . $original . '</code></pre></div>';
        return $res;
    }/*}}}*/

}
