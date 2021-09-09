<?php

namespace jeb\snahp\Apps\Wiki\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class ArticleGroup extends Model
{
    const TABLE_NAME = "phpbb_snahp_wiki_article_group";

    protected $fields;
    protected $requiredFields = ["name", "title", "priority"];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            "name" => new StringField(),
            "title" => new StringField(),
            "priority" => new IntegerField(["default" => 500]),
        ];
    }
}
