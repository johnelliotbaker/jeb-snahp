<?php

namespace jeb\snahp\Apps\Xmas\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\JsonField;

use \R as R;

class Tile extends Model
{
    const TABLE_NAME = 'phpbb_snahp_xmas_tile';

    protected $fields;
    protected $requiredFields = ['name', 'img_url'];

    public function __construct()
    {
        parent::__construct();
        // TODO:: REMOVE
        R::freeze(false);
        $this->fields = [
            'name' => new StringField(),
            'img_url' => new StringField(['default' => '']),
        ];
    }
}
