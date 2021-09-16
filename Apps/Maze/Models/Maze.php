<?php

namespace jeb\snahp\Apps\Maze\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\DateField;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class Maze extends Model
{
    const TABLE_NAME = "phpbb_snahp_maze";

    protected $fields;
    protected $requiredFields = [
        "post",
        "visible",
        "text",
        "createdTime",
        "deadTime",
    ];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            "post" => new IntegerField(["default" => 0]),
            "visible" => new StringField(["default" => "hidden"]),
            "text" => new StringField(["default" => ""]),
            "createdTime" => new DateField(["default" => time()]),
            "deadTime" => new DateField(["default" => time()]),
        ];
    }

    public function deleteWithId($id)
    {
        $id = (int) $id;
        if (!($instance = $this->getObject("id=?", [$id]))) {
            throwHttpException(
                404,
                "Maze(${id}) not found. Error Code: 75d035008c"
            );
        }
        $this->delete($instance);
    }

    public function delete($instance)
    {
        $mazeUsers = getOwnList($instance, MAZE_USER);
        if ($mazeUsers) {
            foreach ($mazeUsers as $mazeUser) {
                R::trash($mazeUser);
            }
        }
        R::trash($instance);
    }
}
