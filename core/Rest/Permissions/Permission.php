<?php

namespace jeb\snahp\core\Rest\Permissions;

class UserPermission extends Permission
{
    use UserModelMixin;

    public function __construct($permissionModel, $userId)
    {
        $this->model = $permissionModel;
        $this->userId = (int) $userId;

        parent::__construct();
    }
}

class AllowNonePermission extends Permission
{
    public function hasPermission($request, $userId, $kwargs = [])
    {
        return false;
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        return false;
    }
}

class AllowLoggedInUserPermission extends Permission
{
    public function hasPermission($request, $userId, $kwargs = [])
    {
        return (int) $userId !== ANONYMOUS;
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        return (int) $userId !== ANONYMOUS;
    }
}

class AllowDevPermission extends Permission
{
    public function __construct($sauth)
    {
        $this->sauth = $sauth;
        parent::__construct();
    }

    public function hasPermission($request, $userId, $kwargs = [])
    {
        if ($this->sauth->is_dev()) {
            return true;
        }
        return false;
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        if ($this->sauth->is_dev()) {
            return true;
        }
        return false;
    }
}

class AllowAnyPermission extends Permission
{
    public function __construct($sauth)
    {
        $this->sauth = $sauth;
        parent::__construct();
    }

    public function hasPermission($request, $userId, $kwargs = [])
    {
        return true;
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        return true;
    }
}

class Permission
{
    const METHOD_TO_ACTION = [
        "GET" => "view",
        "PATCH" => "change",
        "POST" => "add",
        "DELETE" => "delete",
    ];

    const DELIMITER = "__";

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function getAction($request)
    {
        $method = getRequestMethod($request);
        return getDefault($this::METHOD_TO_ACTION, $method, "view");
    }

    public function makePermissionCodename($request, $appLabel, $resourceName)
    {
        $action = $this->getAction($request);
        $delim = $this::DELIMITER;
        return "{$appLabel}.{$action}{$delim}{$resourceName}";
    }

    public function parsePermissionCodename($codename)
    {
        $matches = [];
        $delim = $this::DELIMITER;
        preg_match(
            "/^(\w+)\.([a-zA-Z0-9]+)" . $delim . '(\w+)$/',
            $codename,
            $matches
        );
        if ($matches) {
            return [
                "appLabel" => $matches[1],
                "action" => $matches[2],
                "resourceName" => $matches[3],
            ];
        }
        throw new \Exception(
            "Expected permission codename to be appLabel.action_resourceName. Error Code: b2994ef1ab"
        );
    }

    public function hasPermission($request, $userId, $kwargs = [])
    {
        return false;
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        return false;
    }
}

trait UserModelMixin
{
    protected $model;
    protected $userId;

    public function getUserGroups()
    {
        $sql =
            "SELECT group_id from " .
            USER_GROUP_TABLE .
            " where user_id={$this->userId}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return array_map(function ($arg) {
            return (int) $arg["group_id"];
        }, $rowset);
    }

    public function getPermissions()
    {
        $this->checkModel();
        $groups = $this->getUserGroups($userId);
        return $this->model->getAllowedGroups($groups);
    }

    public function checkModel()
    {
        if (!isset($this->model)) {
            throw new \Exception(
                "Permission model must be defined. Error Code: ccac416a7a"
            );
        }
    }
}
