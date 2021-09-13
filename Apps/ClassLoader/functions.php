<?php

use phpbb\exception\http_exception;

function selectFirstValid(...$args)
{
    foreach ($args as $arg) {
        if ($arg) {
            return $arg;
        }
    }
}

function convertArrayToHTMLAttribute($data)
{
    return htmlspecialchars(json_encode($data), ENT_QUOTES, "UTF-8");
}

function throwHttpException(...$args)
{
    throw new http_exception(...$args);
}
