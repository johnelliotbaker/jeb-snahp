<?php
namespace jeb\snahp\Apps\RequestForm\Models;

class Game extends Base
{
    const TYPE = 'GAME';
    public $contentFields = ['filehost', 'platform'];
}
