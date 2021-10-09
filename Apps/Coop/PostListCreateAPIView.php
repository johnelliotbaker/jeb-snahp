<?php
namespace jeb\snahp\Apps\Coop;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Permissions\AllowLoggedInUserPermission;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;

class PostListCreateAPIView extends ListCreateAPIView
{
    protected $foreignNameParam = "topic";
    public $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    public $request;
    public $sauth;
    public $model;

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;
        $this->permissionClasses = [new AllowLoggedInUserPermission($sauth)];
    }

    public function list($request)
    {
        return new JsonResponse([]);
    }

    public function create($request)
    {
        $this->checkPermissions($request, $this->sauth->userId);
        $data = getRequestData($request);
        $data["user"] = $this->sauth->userId;
        $serializer = $this->getSerializer(null, $data);
        $serializer->fillInitialDataWithDefaultValues();
        if ($serializer->isValid()) {
            $instance = $this->performCreate($serializer);
            return new JsonResponse($instance, 201);
        }
        return new Response("Could not create.", 400);
    }
}
