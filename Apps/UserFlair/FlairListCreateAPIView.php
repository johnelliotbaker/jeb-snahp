<?php

namespace jeb\snahp\Apps\UserFlair;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;


class FlairListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
    protected $request;
    protected $sauth;

    public function __construct($request, $sauth, $Flair)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $Flair;
        $this->permissionClasses = [
            // TODO: Remove after testing
            new AllowAnyPermission($sauth),
            new AllowDevPermission($sauth),
        ];
    }

    public function view()
    {
        return parent::dispatch();
    }
}










// <?php
//
// namespace jeb\snahp\Apps\UserFlair;
//
// require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
// require_once '/var/www/forum/ext/jeb/snahp/Apps/UserFlair/Models/Flair.php';
//
// use jeb\snahp\core\Rest\Views\ListCreateAPIView;
// use jeb\snahp\core\Rest\Serializers\Serializer;
// use jeb\snahp\Apps\UserFlair\Models\Flair;
//
// class MySerializer extends Serializer
// {
//     public function __construct($instance=null, $data=null, $kwargs=[])
//     {
//         parent::__construct($instance, $data, $kwargs);
//         $this->model = new Flair();
//     }
// }
//
//
// class FlairListCreateAPIView extends ListCreateAPIView
// {
//     protected $serializerClass = __NAMESPACE__ . '\MySerializer';
//     protected $request;
//     protected $sauth;
//
//     public function __construct($request, $sauth)
//     {
//         $this->request = $request;
//         $this->sauth = $sauth;
//         $sauth->reject_non_dev('Error Code: bb3484c715');
//         $this->model = new Flair();
//     }
//
//     public function view()
//     {
//         return parent::dispatch();
//     }
// }
