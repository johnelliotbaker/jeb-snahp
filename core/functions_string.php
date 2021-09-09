<?php

function removeTags($strn, $tag)
{
    $ptn = "#\s*\[" . $tag . "]\s*#";
    return preg_replace($ptn, "", $strn);
}

function prependTag($strn, $tag, $suffix = "")
{
    return "[$tag]$suffix$strn";
}
