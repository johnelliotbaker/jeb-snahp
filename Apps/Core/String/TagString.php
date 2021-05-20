<?php
namespace jeb\snahp\Apps\Core\String;

class TagString
{
    public function __construct($tags)/*{{{*/
    {
        $this->def = $tags;
    }/*}}}*/

    public function decodeTags($strn, $tags)
    {
        $decoderTable = $this->def['decode']['small'];
        $prefix = '';
        foreach ($tags as $tag) {
            $prefix .= $decoderTable[$tag];
        }
        return $prefix . $strn;
    }


    public function stripTags($strn)
    {
        $tags = [];
        $encoderTable = $this->def['encode'];
        $ptn = '#\s*((\(|\[|\{)(\w+)(\)|\]|\}))\s*#is';
        $strn = preg_replace_callback(
            $ptn,
            function ($match) use (&$tags, $encoderTable) {
                $tag = strtolower($match[3]);
                if (array_key_exists($tag, $encoderTable)) {
                    $tags[] = $tag;
                    return '';
                }
                return $match[0];
            }, $strn
        );
        $tags = array_unique($tags);
        return [$strn, $tags];
    }

    // public function stripTags($strn)
    // {
    //     // Why the complex encode_tags & decode_tags instead of one pass
    //     // preg_replace? Because of the truncation.
    //     // String truncation passes the string through a urlencoder that
    //     // turns all <> into htmlencoded strings and breaks the html
    //     // So we encode the necessary tags before the string truncate,
    //     // then post-process it after string truncation.
    //     $tags = [];
    //     $encoderTable = $this->def['encode'];
    //     foreach ($encoderTable as $key => $entry) {
    //         $ptn = '#\s*((\(|\[|\{)(' . $key . ')(\)|\]|\}))\s*#is';
    //         $strn = preg_replace_callback(
    //             $ptn,
    //             function ($match) use (&$tags) {
    //                 $tags[] = strtolower($match[3]);
    //                 return '';
    //             }, $strn
    //         );
    //     }
    //     return [$strn, $tags];
    // }



}
