<?php

namespace jeb\snahp\Apps\Boilerplate;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;

// use phpbb\request\request_interface;
// use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;
// use jeb\snahp\Apps\Boilerplate\Models\ModelName;
// use jeb\snahp\core\Rest\Serializers\ModelSerializer;
// use jeb\snahp\core\Rest\Views\ListCreateAPIView;
// use jeb\snahp\core\auth\user_auth;

class ModelNameListCreateAPIView extends ListCreateAPIView
{
    // protected $foreignNameParam = 'urlParam';
    public $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    public $request;
    public $sauth;
    public $model;

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
}
