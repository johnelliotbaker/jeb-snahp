<?php

namespace jeb\snahp\Apps\UserFlair;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;

class TypeListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
    protected $request;
    protected $sauth;

    public function __construct($request, $sauth, $Type)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $Type;
        $this->permissionClasses = [
            new AllowDevPermission($sauth),
        ];
    }

    public function view()
    {
        return parent::dispatch();
    }
}
