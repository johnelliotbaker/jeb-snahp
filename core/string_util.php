<?php

namespace jeb\snahp\core;

class string_util
{
    public function ignore_pattern($f, $ptn)
    {
        $encode = function ($ptn, $strn) {
            $storage = [];
            $html = preg_replace_callback($ptn, function ($match) use (&$storage) {
                $uid = uniqid();
                $storage[$uid] = $match[0];
                return $uid;
            }, $strn);
            return [$html, $storage];
        };
        $decode = function ($strn, $storage) {
            foreach ($storage as $key => $value) {
                $strn = str_replace($key, $value, $strn);
            }
            return $strn;
        };
        $wrapper = function ($strn) use ($f, $ptn, $encode, $decode) {
            [$strn, $storage] = $encode($ptn, $strn);
            $strn = $f($strn);
            $strn = $decode($strn, $storage);
            return $strn;
        };
        return $wrapper;
    }

    public function ignore_codeblock($f)
    {
        $ptn_code = '#\<code\>(.*?)\<\/code\>#s';
        return $this->ignore_pattern($f, $ptn_code);
    }
}
