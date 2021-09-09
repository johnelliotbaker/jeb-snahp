<?php

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
    return htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
}
