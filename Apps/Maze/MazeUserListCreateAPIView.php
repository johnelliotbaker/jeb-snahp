<?php

namespace jeb\snahp\Apps\Maze;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;

class MazeUserListCreateAPIView extends ListCreateAPIView
{
    protected $foreignNameParam = "maze";
    protected $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    protected $request;
    protected $sauth;
    protected $model;

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;
        $this->permissionClasses = [new AllowDevPermission($sauth)];
    }

    public function create($request)
    {
        $this->checkPermissions($request, $this->sauth->userId);
        $requestData = getRequestData($request);
        if (!isset($requestData["username"])) {
            throwHttpException(
                400,
                "Expected a username. Error Code: 4294aa9c85"
            );
        }
        $usernames = array_unique(
            preg_split("/[,\s]+/", $requestData["username"])
        );
        return $this->createMazeUsers($usernames);
    }

    public function createMazeUsers($usernames)
    {
        $res = [
            "success" => [],
            "error" => [],
        ];
        foreach ($usernames as $username) {
            try {
                $this->createMazeUser($username);
                $res["success"][] = $username;
            } catch (\Exception $e) {
                $res["error"][] = $e->getMessage();
            }
        }
        return new JsonResponse($res, 201);
    }

    public function createMazeUser($username)
    {
        $username = utf8_clean_string($username);
        if (!($userId = $this->sauth->userNameToUserId($username))) {
            throw new \Exception("$username: bad username.");
        }
        $foreignPk = $this->getForeignPk(0);
        $MAZE = MAZE;
        $exists = $this->model->getObject("user=? AND ${MAZE}_id=?", [
            $userId,
            $foreignPk,
        ]);
        if ($exists) {
            throw new \Exception("$username: can't be added.");
        }
        $data = [
            "user" => $userId,
        ];
        $serializer = $this->getSerializer(null, $data);
        $serializer->fillInitialDataWithDefaultValues();
        if ($serializer->isValid()) {
            $this->performCreate($serializer);
        }
    }
}
