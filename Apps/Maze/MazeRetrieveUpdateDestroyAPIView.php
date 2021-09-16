<?php

namespace jeb\snahp\Apps\Maze;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\Permission;

class MazeRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    protected $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    protected $request;
    protected $sauth;
    protected $model;

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;
        $this->permissionClasses = [
            new AllowPosterPermission($sauth, $model),
            new AllowDevPermission($sauth),
        ];
    }
}

class AllowPosterPermission extends Permission
{
    public function __construct($sauth, $model)
    {
        $this->sauth = $sauth;
        $this->model = $model;
        parent::__construct();
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        global $db;
        $postId = $object["post"];
        $sqlArray = [
            "SELECT" => "poster_id",
            "FROM" => [POSTS_TABLE => "a"],
            "WHERE" => "a.post_id=${postId}",
        ];
        $data = getRequestData($request);
        if (array_key_exists("visible", $data)) {
            return false;
        }
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $posterId = $row["poster_id"];
        return $posterId == $userId;
    }
}
