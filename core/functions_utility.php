<?php


function fw($content, $filename='/home/ubm/a.txt')
{
    file_put_contents($filename, $content);
}

