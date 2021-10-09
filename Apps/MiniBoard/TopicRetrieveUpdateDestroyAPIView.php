<?php
namespace jeb\snahp\Apps\MiniBoard;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;

class TopicRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    public $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    protected $request;

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $sauth->reject_anon("Error Code: b28a40899f");
        $this->model = $model;
    }

    // public function retrieve($request)
    // {
    //     $instance = $this->getObject();
    //     $instance = $this->model->appendUserInfo($instance);
    //     return new JsonResponse($instance);
    // }
}
