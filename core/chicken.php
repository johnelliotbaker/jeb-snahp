<?php

define('CHICKEN_URL', [
    ['type' => 'image', 'url' => 'https://i.imgur.com/VvtyFxo.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/F0oNN4S.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/nwI3HiL.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/aK1yP0H.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/fpMV6kT.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/nPqrvCV.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/taPLnDc.png'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/aau9LdJ.png'],
]);

define('MOTD', [
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'youtube', 'url' => 'https://www.youtube.com/embed/kTcRRaXV-fg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/X4HOPPN.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/6a7l2Ck.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/bsJmjSE.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/uxSRTwV.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/Ocqi6OJ.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/qET8eHL.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/Pgs3LCn.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/P1LJ0nq.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/mZzjb2m.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/SqkQ0Um.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/mI3ipwt.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/RobBSeW.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/nG6drqB.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/eVsFpXm.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/Acp0eOy.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/haX3gHz.jpg'],
    ['type' => 'image', 'url' => 'https://i.imgur.com/2J0UCHF.jpg'],
]);

function string2cluck($strn)
{
    $n_media = count(MOTD);
    $i_media = rand(0, $n_media-1);
    $media = MOTD[$i_media];
    $n_chicken = count(CHICKEN_URL);
    $arg = [];
    $strn = preg_replace_callback('#(\S)*#is', function ($match) use ($arg) {
        $word = $match[0];
        $len = strlen($word);
        if (rand(0, 10) < 2) {
            $n_chicken = count(CHICKEN_URL);
            $i_chicken = rand(0, $n_chicken-1);
            $img = CHICKEN_URL[$i_chicken];
            $elem = ' <img style="width:24px;" src="' . $img['url'] . '"> ';
            return $elem;
        } elseif ($len < 3) {
            return 'bok ';
        } elseif ($len < 5) {
            return 'cluck ';
        } elseif ($len < 9) {
            if (rand(0, 1) < 1) {
                return 'caw-caw ';
            }
            return 'bah-gawk ';
        } else {
            return 'cock-a-doodle-doo!!! ';
        }
        return 'chirp';
    }, $strn);
    if ($media['type']=='youtube') {
        $media_elem = '<div align="center"><iframe width="400" height="280" src="'. $media['url'] . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    } elseif ($media['type']=='image') {
        $media_elem = '<div align="center"><img style="max-width: 400px;" src="'.$media['url'].'"></img></div>';
    }
    $n_chicken = count(CHICKEN_URL);
    $i_chicken = rand(0, $n_chicken-1);
    $img = CHICKEN_URL[$i_chicken];
    $chicken_img = ' <img style="width:24px;" src="' . $img['url'] . '"> ';
    $strn = $media_elem . $chicken_img . $strn;
    return $strn;
}
