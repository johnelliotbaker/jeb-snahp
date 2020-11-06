<?php
namespace jeb\snahp\Apps\RequestForm\FormTemplateMakers;

const TYPE_TO_LABEL = [
    'filehost' => 'File Host',
    'isbn' => 'ISBN',
    'videoResolution' => 'Resolution',
];

class FormFieldParser
{
    const _PARSERS = [
        'filehost' => 'filehostParser',
        'link' => 'linkParser',
        'isbn' => 'isbnParser',
    ];

    const ALLOWED_FILEHOSTS = ['mega', 'zippy'];

    public function getParser($type)/*{{{*/
    {
        return getDefault(self::_PARSERS, $type, 'stringParser');
    }/*}}}*/

    public function genericCompoundParser($arr)/*{{{*/
    {
        if (!is_array($arr)) {
            return;
        }
        $res = [];
        foreach ($arr as $value) {
            $res[] = (string) $value;
        }
        return implode(', ', $res);
    }/*}}}*/

    public function filehostParser($value)/*{{{*/
    {
        if (!is_string($value)) {
            return;
        }
        $res = [];
        $filehosts = preg_split('#,\s*#s', $value);
        $filehosts = array_unique($filehosts);
        $filehosts = array_filter(
            $filehosts,
            function ($arg) {
                return in_array($arg, self::ALLOWED_FILEHOSTS);
            }
        );
        foreach ($filehosts as $filehost) {
            switch ($filehost) {
            case 'mega':
                $partial = '<img src="https://i.imgur.com/w5aP33F.png" title="mega" style="height: 2.0em;">';
                break;
            case 'zippy':
                $partial = '<img src="https://i.imgur.com/qD95AzT.png" title="zippy" style="height: 2.0em;">';
                break;
            default:
                $partial = '';
            }
            $res[] = $partial;
        }
        return implode('<span style="margin-left:8px;margin-right:8px;">or</span>', $res);
    }/*}}}*/

    public function isbnParser($isbn)/*{{{*/
    {
        if (!is_numeric($isbn)) {
            return;
        }
        return "<a href=\"https://catalog.loc.gov/vwebv/search?searchArg=$isbn&searchCode=GKEY%5E*&searchType=1\" class=\"isbn\" target=\"_blank\">$isbn</a>";
    }/*}}}*/

    public function linkParser($link)/*{{{*/
    {
        return "<a href=\"$link\" target=\"_blank\" class=\"link\">$link</a>";
    }/*}}}*/

    public function nullParser($value)/*{{{*/
    {
    }/*}}}*/

    public function stringParser($value)/*{{{*/
    {
        return $value;
    }/*}}}*/

    public function parse($type, $value)/*{{{*/
    {
        $parser = $this->getParser($type);
        return $this->{$parser}($value);
    }/*}}}*/
}

class FormTemplateMaker
{
    const _BANNERS = [
        'game' => 'https://i.imgur.com/5WhPsfz.png',
        'anime' => 'https://i.imgur.com/CelJcub.png',
        'ebook' => 'https://i.imgur.com/aARcbcT.png',
    ];

    public function parseData($data)/*{{{*/
    {
        $res = [];
        $formFieldParser = new FormFieldParser();
        foreach ($data as $type => $value) {
            $typeDisplayName = getDefault(TYPE_TO_LABEL, $type, ucfirst($type));
            $res[$typeDisplayName] = $formFieldParser->parse($type, $value);
        }
        return $res;
    }/*}}}*/

    public function getBannerImage($type)/*{{{*/
    {
        return getDefault(self::_BANNERS, $type);
    }/*}}}*/

    public function makeRequestTemplateHTML($data)/*{{{*/
    {
        $type = getDefault($data, 'type', '');
        $title = getDefault($data, 'title', 'No Title');
        $bannerImageURL = $this->getBannerImage($type);
        $content = getDefault($data, 'content', []);
        $content = $this->parseData($content);
        $renderer = getTwigRenderer(['/var/www/forum/ext/jeb/snahp/Apps/RequestForm/FormTemplateMakers']);
        $html = $renderer->render(
            'main.html.twig',
            [
                'title' => $title,
                'bannerImageURL' => $bannerImageURL,
                'content' => $content,
            ]
        );
        return $html;
    }/*}}}*/
}
