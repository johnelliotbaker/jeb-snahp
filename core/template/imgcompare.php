<?php
namespace jeb\snahp\core\template;

class imgcompare
{
    public function to_html($argsAll)/*{{{*/
    {
        $data = [];
        foreach (explode(PHP_EOL, $argsAll) as $k => $arg) {
            $args = explode('`', $arg);
            if (count($args) < 2) {
                continue;
            }
            for ($i=count($args); $i<4; $i++) {
                $args[$i] = '';
            }
            $url1 = trim($args[0]);
            $url2 = trim($args[1]);
            if (!filter_var($url1, FILTER_VALIDATE_URL) || !filter_var($url2, FILTER_VALIDATE_URL)) {
                continue;
            }
            $label1 = (trim($args[2]));
            $label2 = (trim($args[3]));
            $entry = [
                'url1' => $url1,
                'url2' => $url2,
                'label1' => $label1,
                'label2' => $label2,
            ];
            $data[] = $entry;
        }
        $data = convertArrayToHTMLAttribute($data);
        $html = '<span class="imagecompare" data-data="' . $data . '"></span>';
        return $html;
    }/*}}}*/
}
