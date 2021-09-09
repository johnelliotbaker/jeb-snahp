<?php

namespace jeb\snahp\Apps\MiniBoard\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\DateField;

class Post extends Model
{
    const TABLE_NAME = "minipost";
    const FOREIGN_NAME = "minitopic";

    protected $fields;
    protected $requiredFields = [];

    public function __construct()
    {
        parent::__construct();

        $this->fields = [
            "text" => new StringField(),
            "created" => new DateField(),
        ];
    }
}
