<?php

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "$k => "; prn($v); }
    } else { echo "$var<br>"; }
}

function filter_quote($strn)
{
    $ptn = '#(\[(quote|\/quote)|$)#is';
    $start = 0;
    preg_match($ptn, $strn, $match, PREG_OFFSET_CAPTURE);
    $stack = [];
    $non_quoted = [];
    while ($match)
    {
        $n_stack = count($stack);
        $i_match = $match[0][1];
        $word = $match[0][0];
        prn($word);
        if ($word == '[quote' || !$word)
        {
            if (count($stack) == 0)
            {
                $partial = substr($strn, $start, $i_match-$start);
                $non_quoted[] = $partial;
            }
            $stack[] = 1;
        }
        else
        {
            array_pop($stack);
        }
        $start = $i_match + 8;
        preg_match($ptn, $strn, $match, PREG_OFFSET_CAPTURE, $start);
    }
    return implode(' ' , $non_quoted);
}
