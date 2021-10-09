<?php
namespace jeb\snahp\Apps\Coop;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";
require_once "/var/www/forum/ext/jeb/snahp/Apps/Coop/Permissions/AllowOpPermission.php";

use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\Apps\Coop\Permissions\AllowOpPermission;
use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;

class TopicRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
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
            new AllowOpPermission($sauth),
            new AllowDevPermission($sauth),
        ];
    }
}
