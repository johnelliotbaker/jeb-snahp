<?php

namespace jeb\snahp\Apps\MiniBoard\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\CommaSeparatedPositiveIntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

class Forum extends Model
{
    const TABLE_NAME = MINIFORUMS_TABLE;

    protected $fields;
    protected $requiredFields = ["mainpost", "owner"];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            "mainpost" => new IntegerField(),
            "owner" => new IntegerField(),
            "moderators" => new CommaSeparatedPositiveIntegerField(),
        ];
    }

    // public function performPreCreate($instance)
    // {
    //     // TODO: Remove after initial run
    //     // $res = \R::getWriter()->addUniqueIndex($this::TABLE_NAME, ['mainpost']);
    // }
}
