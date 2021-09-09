<?php
namespace jeb\snahp\Apps\Xmas\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\JsonField;

use \R as R;

use jeb\snahp\Apps\Xmas\BingoBoard;

class Config extends Model
{
    const TABLE_NAME = 'phpbb_snahp_xmas_config';

    protected $fields;
    protected $requiredFields = ['name', 'data'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'name' => new StringField(),
            'data' => new JsonField(['default' => json_encode([])]),
        ];
    }
}
