<?php

function selectFirstValid(...$args)
{
    foreach ($args as $arg) {
        if ($arg) {
            return $arg;
        }
    }
}
