<?php

namespace jeb\snahp\Apps\TopicUpdateTicker\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

R::freeze(false);
class Entry extends Model
{
    const TABLE_NAME = "phpbb_snahp_tut_entry";
    // const FOREIGN_NAME = 'phpbb_snahp_wiki_article_group';
    // To use foreign key, must set FOREIGN_NAME in model and $foreignNameParam in APIView

    protected $fields;
    protected $requiredFields = ["topic", "text"];

    public function __construct()
    {
        // R::exec("DROP TABLE " . $this::TABLE_NAME);
        parent::__construct();
        $this->fields = [
            "topic" => new IntegerField(),
            "text" => new StringField(),
            "created" => new IntegerField(["default" => time()]),
        ];
    }
}
