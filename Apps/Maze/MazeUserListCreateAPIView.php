<?php

namespace jeb\snahp\Apps\Maze;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;

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
        $this->permissionClasses = [
            new AllowDevPermission($sauth),
            // new AllowAnyPermission($sauth),
        ];
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
        $username = utf8_clean_string($requestData["username"]);
        if (!($userId = $this->sauth->userNameToUserId($username))) {
            throwHttpException(
                404,
                "Username ${username} not found. Error Code: bdae3285f5"
            );
        }
        $foreignPk = $this->getForeignPk(0);
        $MAZE = MAZE;
        $exists = $this->model->getObject("user=? AND ${MAZE}_id=?", [
            $userId,
            $foreignPk,
        ]);
        if ($exists) {
            $message =
                "Cannot make a duplicate maze user. Error Code: b3713b0a77";
            throwHttpException(409, $message);
        }
        $data = [
            "user" => $userId,
        ];
        $serializer = $this->getSerializer(null, $data);
        $serializer->fillInitialDataWithDefaultValues();
        if ($serializer->isValid()) {
            $instance = $this->performCreate($serializer);
            return new JsonResponse($instance, 201);
        }
        return new Response("Could not create.", 400);
    }
}
