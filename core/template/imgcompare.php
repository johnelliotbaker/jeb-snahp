<?php
namespace jeb\snahp\core\template;

function extractURL($strn)
{
    $success = preg_match('#<a href="(.*?)"#', $strn, $matches);
    return $success ? $matches[1] : $strn;
}

class imgcompare
{
    public function toHtml($argsAll)
    {
        $data = [];
        foreach (explode(PHP_EOL, $argsAll) as $k => $arg) {
            $arg = str_replace("<br>", "", $arg);
            $args = explode("`", $arg);
            if (count($args) < 2) {
                continue;
            }
            for ($i = count($args); $i < 4; $i++) {
                $args[$i] = "";
            }
            $url1 = extractURL(trim($args[0]));
            $url2 = extractURL(trim($args[1]));
            if (
                !filter_var($url1, FILTER_VALIDATE_URL) ||
                !filter_var($url2, FILTER_VALIDATE_URL)
            ) {
                continue;
            }
            $label1 = trim($args[2]);
            $label2 = trim($args[3]);
            $entry = [
                "url1" => $url1,
                "url2" => $url2,
                "label1" => $label1,
                "label2" => $label2,
            ];
            $data[] = $entry;
        }
        $data = convertArrayToHTMLAttribute($data);
        $html = '<span class="imagecompare" data-data="' . $data . '"></span>';
        return $html;
    }
}
