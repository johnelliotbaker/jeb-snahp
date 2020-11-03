<?php
namespace jeb\snahp\Apps\RequestForm\Models;

class Anime extends Base
{
    const TYPE = 'ANIME';
    public $contentFields = [
        'filehost',
        'videoResolution',
        'videoCodec',
        'audio',
        'subtitle',
        'link'
    ];
}
