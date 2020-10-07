<?php

namespace jeb\snahp\Apps\UserFlair\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\JsonField;

class Flair extends Model
{
    // const TABLE_NAME = MINIFLAIRS_TABLE;
    const TABLE_NAME = 'phpbb_snahp_flr_flair';

    protected $fields;
    protected $requiredFields = ['user', 'type', 'data'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'user' => new IntegerField(),
            'type' => new StringField(),
            'data' => new JsonField(),
        ];
    }
}
