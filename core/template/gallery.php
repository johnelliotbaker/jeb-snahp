<?php
namespace jeb\snahp\core\template;

function getClipboardEl($options)
{
    $clipboardEl =
        '<div class="clipboard" onClick="Clipboard.copy_gallery_link(event);"><i class="icon fa-clipboard fa-fw icon-black" aria-hidden="true"></i></div>';
    switch ($options["clipboard"]) {
        case "none":
            return "";
        default:
            return $clipboardEl;
    }
}

function getPastebin($options, $data)
{
    if (!$data) {
        return "";
    }
    $pastebinEl =
        '<div class="pastebin"><a href="' .
        $data .
        '" target="_blank"><img src="https://upload.wikimedia.org/wikipedia/en/3/35/Pastebin.com_logo.png"></a></div>';
    switch ($options["paste"]) {
        case "none":
            return "";
        default:
            return $pastebinEl;
    }
}
class gallery
{
    protected $def;
    protected $allowed_interpreted_tags;
    public function __construct()
    {
        $column_sizes = [
            "sm" => "col-lg-2 col-3 sm",
            "default" => "col-lg-3 col-sm-4 col-6 default",
            "lg" => "col-lg-4 col-sm-6 col-12 lg",
        ];
        $def["column_sizes"] = $column_sizes;
        $this->def = $def;
        $this->allowed_interpreted_tags = join("|", ["b", "sm", "br"]);
    }

    private function set_default_options($options)
    {
        $defaultOptions = [
            "size" => "default",
            "justify_content" => "justify-content-left",
            "clipboard" => "default",
            "paste" => "default",
        ];
        return $this->options = array_merge($defaultOptions, $options);
    }

    public function handle($mode, $data, $options = [])
    {
        $options = $this->set_default_options($options);
        switch ($mode) {
            case "compact":
                return $this->handle_compact($data, $options);
            case "grid":
                return $this->handle_grid($data, $options);
            case "cards":
                return $this->handle_cards($data, $options);
            default:
                break;
        }
        return "";
    }

    private function is_json($strn)
    {
        json_decode($strn);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function generate_menu_from_json_url($d, $choice)
    {
        if ($choice === 1) {
            return "";
        }
        $strn = "";
        if (isset($d[5]) && $d[5]) {
            $strn = $d[5];
        }
        if ($this->is_json($strn)) {
            $json = json_decode($strn, true);
            if (array_key_exists("menu", $json)) {
                $menu_data = $json["menu"];
                $strn = json_encode($menu_data, JSON_HEX_APOS);
                $data_string = htmlspecialchars($strn, ENT_QUOTES, "UTF-8");
                return '<div class="rx_menu" data-data="' .
                    $data_string .
                    '"></div>';
            }
        }
        return "";
    }

    private function handle_cards($data, $options = [])
    {
        $column_size = $this->def["column_sizes"][$options["size"]];
        $html["begin"][] =
            '<link rel="stylesheet" type="text/css" href="/ext/jeb/snahp/styles/all/template/gallery/component/cards/base.css">';
        $html["begin"][] = '<div class="twbs">';
        $html["begin"][] = '<section class="gallery-block cards-gallery">';
        $html["begin"][] = '<div class="container">';
        $html["begin"][] =
            '<div class="row ' . $options["justify_content"] . '">';
        $html["end"][] = "</div></div></section></div>";
        $ptn = '<dl class="hidebox (\w+)">';
        $class = ["", " hi"];
        $elem = ["a", "span"];
        foreach ($data as $d) {
            $link = strip_tags($d[2]);
            $choice = 0;
            preg_match($ptn, $d[2], $match);
            if (count($match) > 0) {
                if ($match[1] == "hi") {
                    $choice = 1;
                }
            }
            $rx_menu_html = $this->generate_menu_from_json_url($d, $choice);
            $pastebin = "";
            if (isset($d[4]) && $d[4]) {
                $pastebin =
                    '<div class="pastebin"><a href="' .
                    $d[4] .
                    '" target="_blank"><img src="https://upload.wikimedia.org/wikipedia/en/3/35/Pastebin.com_logo.png"></a></div>';
            }
            $cls = $class[$choice];
            $el = $elem[$choice];
            $d[0] = preg_replace("#&lt;(br)&gt;#", '<\1>', $d[0]);
            $d[1] = preg_replace(
                "#&lt;((/)?(" . $this->allowed_interpreted_tags . "))&gt;#",
                '<\1>',
                $d[1]
            );
            $body[] = "<div class='$column_size item$cls'><div class='card border-0'>";
            $body[] = $pastebin;
            $body[] = getClipboardEl($options);
            $body[] = "<$el href='$link' target='_blank'>";
            $body[] = "<img src='$d[3]' alt='Card Image' class='card-img-top' loading='lazy'>";
            $body[] = "<div class='hiddencorner $cls'>";
            $body[] = "<img src='https://i.imgur.com/Q07cXb4.png'></div> </$el>";
            $body[] = "<div class='card-body'><h6>$d[0]</h6>";
            $body[] = "<p class='text-muted card-text'>$d[1]</p>";
            $body[] = $rx_menu_html;
            $body[] = "</div></div></div>";
        }
        $html["body"] = $body;
        $sequence = ["begin", "body", "end"];
        $res = "";
        foreach ($sequence as $key) {
            $res .= join(PHP_EOL, $html[$key]);
        }
        return $res;
    }

    private function handle_grid($data, $options = [])
    {
        $column_size = $this->def["column_sizes"][$options["size"]];
        $html["begin"][] =
            '<link rel="stylesheet" type="text/css" href="/ext/jeb/snahp/styles/all/template/gallery/component/grid/base.css">';
        $html["begin"][] = '<div class="twbs">';
        $html["begin"][] =
            '<section class="gallery-block grid-gallery"><div class="container"><div class="row">';
        $html["end"][] = "</div></div></section></div>";
        $ptn = '<dl class="hidebox (\w+)">';
        $class = ["", " hi"];
        $elem = ["a", "span"];
        foreach ($data as $d) {
            $link = strip_tags($d[2]);
            $choice = 0;
            preg_match($ptn, $d[2], $match);
            if (count($match) > 0) {
                if ($match[1] == "hi") {
                    $choice = 1;
                }
            }
            $cls = $class[$choice];
            $el = $elem[$choice];
            $body[] = "<div class='$column_size item$cls'>";
            $body[] = "<$el href='$link' target='_blank'>";
            $body[] = "<img class='img-fluid image scale-on-hover' src='$d[3]' loading='lazy'>";
            $body[] = "</$el></div>";
        }
        $html["body"] = $body;
        $sequence = ["begin", "body", "end"];
        $res = "";
        foreach ($sequence as $key) {
            $res .= join(PHP_EOL, $html[$key]);
        }
        return $res;
    }

    private function handle_compact($data, $options = [])
    {
        $column_size = $this->def["column_sizes"][$options["size"]];
        $html["begin"][] =
            '<link rel="stylesheet" type="text/css" href="/ext/jeb/snahp/styles/all/template/gallery/component/compact/base.css">';
        $html["begin"][] = '<div class="twbs">';
        $html["begin"][] =
            '<section class="gallery-block compact-gallery"><div class="container">';
        $justify = $options["justify_content"];
        $html["begin"][] = "<div class='row no-gutters $justify'>";
        $html["end"][] = "</div></div></section></div>";
        $ptn = '<dl class="hidebox (\w+)">';
        $class = ["", " hi"];
        $elem = ["a", "span"];
        foreach ($data as $d) {
            $link = strip_tags($d[2]);
            $choice = 0;
            preg_match($ptn, $d[2], $match);
            if (count($match) > 0) {
                if ($match[1] == "hi") {
                    $choice = 1;
                }
            }
            $pastebin = getPastebin($options, $d[4]);
            // $pastebin = "";
            // if (isset($d[4])) {
            //     $pastebin =
            //         '<div class="pastebin"><a href="' .
            //         $d[4] .
            //         '" target="_blank"><img src="https://upload.wikimedia.org/wikipedia/en/3/35/Pastebin.com_logo.png"></a></div>';
            // }
            $cls = $class[$choice];
            $el = $elem[$choice];
            $d[0] = preg_replace("#&lt;(br)&gt;#", '<\1>', $d[0]);
            $d[1] = preg_replace(
                "#&lt;((/)?(" . $this->allowed_interpreted_tags . "))&gt;#",
                '<\1>',
                $d[1]
            );
            $body[] = "<div class='$column_size item zoom-on-hover $cls'>";
            $body[] = $pastebin;
            $body[] = getClipboardEl($options);
            $body[] = "<$el href='$link' target='_blank'>";
            $body[] = "<img class='img-fluid image' src='$d[3]' loading='lazy'>";
            $body[] = "<div class='hiddencorner $cls'>";
            $body[] = '<img src="https://i.imgur.com/Q07cXb4.png">';
            $body[] = "</div>";
            $body[] = '<span class="description">';
            $body[] = "<span class='description-heading'>$d[0]</span>";
            $body[] = "<span class='description-body'>$d[1]</span>";
            $body[] = "</span></$el></div>";
        }
        $html["body"] = $body;
        $sequence = ["begin", "body", "end"];
        $res = "";
        foreach ($sequence as $key) {
            $res .= join(PHP_EOL, $html[$key]);
        }
        return $res;
    }
}
