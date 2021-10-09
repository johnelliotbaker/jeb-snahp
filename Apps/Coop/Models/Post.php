<?php
namespace jeb\snahp\Apps\Coop\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

class Post extends Model
{
    const TABLE_NAME = TABLE_COOP_POST;
    const FOREIGN_NAME = TABLE_COOP_TOPIC;

    protected $fields;
    protected $requiredFields = [
        "user",
        "text",
        "visible",
        "created",
        "modified",
    ];

    public function __construct()
    {
        parent::__construct();
        $time = time();
        $this->fields = [
            "visible" => new StringField(["default" => "public"]),
            "text" => new StringField(["default" => "", "max_length" => 60000]),
            "user" => new IntegerField(["default" => ANONYMOUS]),
            "created" => new IntegerField(["default" => $time]),
            "modified" => new IntegerField(["default" => $time]),
        ];
    }
}
