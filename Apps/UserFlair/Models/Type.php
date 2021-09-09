<?php

namespace jeb\snahp\Apps\UserFlair\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\CommaSeparatedStringField;

class Type extends Model
{
    // const TABLE_NAME = MINIFLAIRS_TABLE;
    const TABLE_NAME = 'phpbb_snahp_flr_type';

    protected $fields;
    protected $requiredFields = ['name', 'data'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'name' => new StringField(),
            'data' => new StringField(),
            // 'fields' => new CommaSeparatedStringField(),
        ];
    }

    // public function performPreCreate($instance)
    // {
    //     // TODO: Remove after initial run
    //     // $res = \R::getWriter()->addUniqueIndex($this::TABLE_NAME, ['mainpost']);
    // }
}
