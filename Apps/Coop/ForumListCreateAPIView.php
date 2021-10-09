<?php
namespace jeb\snahp\Apps\Coop;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Permissions\AllowLoggedInUserPermission;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;

class ForumListCreateAPIView extends ListCreateAPIView
{
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
}
