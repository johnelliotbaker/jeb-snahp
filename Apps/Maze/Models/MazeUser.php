<?php

namespace jeb\snahp\Apps\Maze\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

class MazeUser extends Model
{
    const TABLE_NAME = "phpbb_snahp_maze_user";
    const FOREIGN_NAME = "phpbb_snahp_maze";

    protected $fields;
    protected $requiredFields = ["user", "user"];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            "user" => new IntegerField([
                "default" => 2,
                "name" => "MazeUser.user",
            ]),
            "log" => new StringField([
                "default" => "",
                "name" => "MazeUser.log",
            ]),
        ];
    }
}
