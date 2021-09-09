<?php

namespace jeb\snahp\Apps\Boilerplate\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

// R::freeze(false);
class ModelName extends Model
{
    const TABLE_NAME = "phpbb_snahp_";
    // const FOREIGN_NAME = 'phpbb_snahp_wiki_article_group';
    // To use foreign key, must set FOREIGN_NAME in model and $foreignNameParam in APIView

    protected $fields;
    protected $requiredFields = ["name", "number"];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            "name" => new StringField(["default" => "something"]),
            "number" => new IntegerField(["default" => 500]),
        ];
    }
}
