<?php

function prn($var, $b_html=false, $depth=0)
{/*{{{*/
    $indent = [];
    for ($i=0; $i<$depth; $i++)
    {
        $indent[] = '...';
    }
    $indent = join('', $indent);
    if (is_array($var))
    { foreach ($var as $k => $v) { echo '<br>'; echo "$indent$k => "; prn($v, $b_html, $depth+1); }
    } else {
        if ($b_html)
        {
            echo htmlspecialchars($var);
        }
        else
        {
            echo $var . '<br>';
        }
    }
}/*}}}*/

function fw($var, $filename='/home/ubm/a.txt', $depth=0)
{
    if ($depth>10) return false;
    $indent = [];
    for ($i=0; $i<$depth; $i++)
    {
        $indent[] = '.....';
    }
    $indent = join('', $indent);
    if (is_array($var))
    { 
        foreach ($var as $k => $v)
        {
            $t = PHP_EOL . "$indent$k => ";
            file_put_contents($filename, $t, FILE_APPEND);
            fw($v, $filename, $depth+1);
        }
    }
    else
    {
        file_put_contents($filename, $var, FILE_APPEND);
    }
}
