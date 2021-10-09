<?php
namespace jeb\snahp\Apps\Coop\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

class Topic extends Model
{
    const TABLE_NAME = TABLE_COOP_TOPIC;
    const FOREIGN_NAME = TABLE_COOP_FORUM;

    protected $fields;
    protected $requiredFields = [
        "title",
        "user",
        "visible",
        "created",
        "modified",
    ];

    public function __construct()
    {
        parent::__construct();
        $time = time();
        $this->fields = [
            "title" => new StringField(["default" => "", "max_length" => 200]),
            "user" => new IntegerField(),
            "visible" => new StringField(["default" => "public"]),
            "created" => new IntegerField(["default" => $time]),
            "modified" => new IntegerField(["default" => $time]),
        ];
    }
}
