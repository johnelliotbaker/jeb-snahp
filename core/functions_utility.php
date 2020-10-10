<?php

function prn($var, $b_html=false, $depth=0)
{
    $nl = $b_html ? '<br>' : PHP_EOL;
    $nbsp = $b_html ? '&nbsp;' : ' ';
    $tab = $nbsp . $nbsp . $nbsp . $nbsp;
    if ($depth===0) {
        $stack = debug_backtrace();
        array_shift($stack);
        foreach ($stack as $call) {
            echo $nl . $tab . $call['class'] . '->' . $call['function'];
        }
        echo $nl . $tab . '----------------------------------------------------------------------------------------------------';
    }
    $indent = [];
    for ($i=0; $i<$depth; $i++) {
        $indent[] = '...';
    }
    $indent = join('', $indent);
    if (is_array($var)) {
        foreach ($var as $k => $v) {
            echo $nl;
            echo "$indent$k => ";
            prn($v, $b_html, $depth+1);
        }
    } else {
        if ($b_html) {
            echo htmlspecialchars($var);
        } else {
            echo $var;
        }
    }
    if ($depth===0) {
        echo $nl;
    }
}

function fclear($filename='/home/ubm/a.txt', $depth=0, $b_new=true)
{
    file_put_contents($filename, '');
}

function fw($var, $filename='/home/ubm/a.txt', $depth=0)
{
    if ($depth>10) {
        return false;
    }
    $indent = [];
    for ($i=0; $i<$depth; $i++) {
        $indent[] = '.....';
    }
    $indent = join('', $indent);
    if (is_array($var)) {
        foreach ($var as $k => $v) {
            $t = PHP_EOL . "$indent$k => ";
            file_put_contents($filename, $t, FILE_APPEND);
            fw($v, $filename, $depth+1);
        }
    } else {
        file_put_contents($filename, $var, FILE_APPEND);
    }
}

function getDefault($dict, $key, $defualt=null)
{
    if (!array_key_exists($key, $dict)) {
        return $defualt;
    }
    return $dict[$key];
}

function uuid4()
{
    // https://www.php.net/manual/en/function.uniqid.php
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function getStyleName()
{
    global $db, $user;
    $sql = 'SELECT style_name FROM ' . STYLES_TABLE . '
        WHERE style_id=' . (int) $user->data['user_style'];
    $result = $db->sql_query($sql, 3600);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    switch ($row['style_name']) {
    case 'Hexagon':
        return [$row['style_name'], 'hexagon'];
    case 'Acieeed!':
        return [$row['style_name'], 'acieeed!'];
    case 'prosilver':
        return [$row['style_name'], 'prosilver'];
    case 'Basic':
        return [$row['style_name'], 'basic'];
    case 'Digi Orange':
    default:
        return [$row['style_name'], 'digi_orange'];
    }
}
