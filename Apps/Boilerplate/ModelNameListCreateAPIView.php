<?php

namespace jeb\snahp\Apps\Wiki;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;

class ModelNameListCreateAPIView extends ListCreateAPIView
{
    // protected $foreignNameParam = 'urlParam';
    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
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
}
