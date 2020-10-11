<?php

namespace jeb\snahp\Apps\Boilerplate;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;

class ModelNameRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
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
