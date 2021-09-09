<?php

define("DEFINITION", [
    "music" => [
        "padding" => [20],
        "Host" => [
            "req_v_host" => ["", ""],
            "req_v_host_mega" => ["MEGA", ""],
            "req_v_host_zippy" => ["Zippyshare", ""],
        ],
        "Compression" => [
            "req_v_comp_flac" => ["FLAC", ""],
            "req_v_comp_mp3" => ["MP3", ""],
            "req_v_comp_wav" => ["WAV", ""],
        ],
        "Minimum Bitrate" => [
            "req_a_min_bitrate" => [
                "96 kbps",
                "128 kbps",
                "160 kbps",
                "192 kbps",
                "256 kbps",
                "320 kbps",
            ],
        ],
        "Sample Rate" => ["req_a_min_sample" => ["44100 Hz", "48000 Hz"]],
        "Channels" => ["req_a_channel" => ["Mono", "Stereo"]],
        "Bit Depth" => [
            "req_a_bitrate" => ["8-Bit", "16-Bit", "24-Bit", "32-Bit"],
        ],
        "Source" => [
            "req_v_src_bluray" => ["Blu-Ray", ""],
            "req_v_src_dvd" => ["DVDRip", ""],
            "req_v_src_web" => ["Web", ""],
            "req_v_src_hdtv" => ["HDTV", ""],
            "req_v_src_sdtv" => ["SDTV", ""],
            "req_v_src_cam" => ["CAM/TS", ""],
        ],
        "Size" => [
            "req_v_size_min" => ["Minimum Size", 0],
            "req_v_size_max" => ["Maximum Size", 0],
        ],
        "Quality Balance" => [
            "req_quality_balance" => ["", ""],
        ],
        "Link" => [
            "req_v_reference_url" => ["Reference Link", ""],
        ],
    ],
    "video" => [
        "padding" => [18],
        "Host" => [
            "req_v_host" => ["", ""],
            "req_v_host_mega" => ["MEGA", ""],
            "req_v_host_zippy" => ["Zippyshare", ""],
        ],
        "Resolution" => [
            "req_v_res_sd" => ["SD/480p", ""],
            "req_v_res_720" => ["720p", ""],
            "req_v_res_1080" => ["1080p", ""],
            "req_v_res_4k" => ["4K", ""],
        ],
        "Codec" => [
            "req_v_codec_264" => ["x264", ""],
            "req_v_codec_265" => ["x265", ""],
            "req_v_codec_VP9" => ["VP9", ""],
            "req_v_codec_xvid" => ["XviD", ""],
        ],
        "Source" => [
            "req_v_src_bluray" => ["Blu-Ray", ""],
            "req_v_src_dvd" => ["DVDRip", ""],
            "req_v_src_web" => ["Web", ""],
            "req_v_src_hdtv" => ["HDTV", ""],
            "req_v_src_sdtv" => ["SDTV", ""],
            "req_v_src_cam" => ["CAM/TS", ""],
        ],
        "Format" => [
            "req_v_format_3d" => ["3D", ""],
            "req_v_format_fulldisc" => ["Full Disc", ""],
            "req_v_format_remux" => ["Remux", ""],
            "req_v_format_encode" => ["Encode", ""],
        ],
        "Size" => [
            "req_v_size_min" => ["Minimum Size", 0],
            "req_v_size_max" => ["Maximum Size", 0],
        ],
        "Quality Balance" => [
            "req_quality_balance" => ["", ""],
        ],
        "Link" => [
            "req_v_reference_url" => ["Reference Link", ""],
        ],
    ],
]);

function make_request_form($request)
{
    $res = [];
    $res[] = "{snahp}{table}" . PHP_EOL;
    $varnames = $request->variable_names();
    $type = $request->variable("request_type", "video");
    if (in_array($type, ["tv", "movie", "video"])) {
        $type = "video";
    }
    $definition = DEFINITION[$type];
    $n_padding = $definition["padding"][0];
    foreach ($definition as $attr_name => $attr) {
        $data = [];
        foreach ($attr as $key => $val) {
            $var = $request->variable($key, $val[1]);
            if (!$var && $var !== 0) {
                continue;
            }
            switch ($key) {
                case "req_v_host":
                    $b_mega = stripos($var, "mega");
                    $b_zippy = stripos($var, "zippy");
                    if ($b_mega !== false) {
                        $data[] =
                            '{img class="host-icon" src="//i.imgur.com/kkmC4dv.png"}{/img}';
                    }
                    if ($b_zippy !== false) {
                        $data[] =
                            '{img class="host-icon" src="//i.imgur.com/EO7Nyo7.png"}{/img}';
                    }
                    break;
                case "req_quality_balance":
                    $data[] = $var;
                    break;
                case "req_v_size_min":
                    $data[] = (int) $var;
                    break;
                case "req_v_size_max":
                    $data[] = (int) $var;
                    break;
                case "req_v_reference_url":
                    break;
                default:
                    if ($var[0] == "x") {
                        $i = (int) $var[1];
                        $data[] = $val[$i];
                    } elseif ($var == 2) {
                        $elem = '{span class="bold"}' . $val[0] . "{/span}";
                        array_unshift($data, $elem);
                    } else {
                        $data[] = $val[0];
                    }
                    break;
            }
        }
        if ($data) {
            if ($attr_name == "Link") {
                continue;
            }
            // Skip link
            elseif ($attr_name == "Size") {
                if ($data[1] <= 0) {
                    continue;
                } else {
                    $data[] = array_pop($data) . " GB";
                    $res[] = "{tr}" . PHP_EOL;
                    $strn = implode(" to ", $data);
                    $res[] =
                        "    {td}$attr_name{/td}" .
                        PHP_EOL .
                        "    {td}$strn{/td}" .
                        PHP_EOL;
                    $res[] = "{/tr}" . PHP_EOL;
                }
            } else {
                $res[] = "{tr}" . PHP_EOL;
                $strn = implode(" or ", $data);
                $res[] =
                    "    {td}$attr_name{/td}" .
                    PHP_EOL .
                    "    {td}$strn{/td}" .
                    PHP_EOL;
                $res[] = "{/tr}" . PHP_EOL;
            }
        }
    }
    $url = $request->variable("req_v_reference_url", "");
    if ($url) {
        $parsed_url = parse_url($url);
        $host = $parsed_url["host"];
        $res[] = "{tr}" . PHP_EOL;
        $res[] =
            "{td} Link {/td}{td} " .
            "{a href=\"$url\" target=\"_blank\"}$host" .
            " {/a}{/td}" .
            PHP_EOL;
        $res[] = "{/tr}" . PHP_EOL;
    }
    $res[] = "{/table}{/snahp}" . PHP_EOL;
    $res[] =
        "[center]* Items in [b]bold[/b] are required or strongly preferred.[/center]";
    $res = implode("", $res);
    return $res;
}
