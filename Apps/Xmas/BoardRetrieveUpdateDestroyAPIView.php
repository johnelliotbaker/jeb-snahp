<?php

namespace jeb\snahp\Apps\Xmas;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;

class BoardRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    // protected $foreignNameParam = 'urlParam';
    protected $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    protected $request;
    protected $sauth;
    protected $model;
    protected $userId;

    public function __construct($request, $sauth, $helper, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->model = $model;
        $this->permissionClasses = [
            new AllowDevPermission($sauth),
            // new AllowAnyPermission($sauth),
        ];
        $this->userId = $this->sauth->userId;
    }

    public function getObject()
    {
        $sequence = [5, 6, 2, 8, 10, 11, 4];
        $instance = parent::getObject();
        print_r($this->helper->getEventIndex());
        $data = ["score" => $this->helper->score($this->userId)];
        return $this->model->update($instance, $data);
    }
}
