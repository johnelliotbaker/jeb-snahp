<?php

namespace jeb\snahp\Apps\MiniBoard;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Paginations/PageNumberPagination.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";

use jeb\snahp\core\Rest\Views\ListCreateAPIView;

class TopicListCreateAPIView extends ListCreateAPIView
{
    public $serializerClass = "jeb\snahp\core\Rest\Serializers\ModelSerializer";
    protected $paginationClass = "jeb\snahp\core\Rest\Paginations\PageNumberPagination";
    protected $request;

    protected $foreignNameParam = "miniforum";

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $sauth->reject_anon("Error Code: b28a40899f");
        $this->model = $model;
        $this->paginator = new $this->paginationClass();
    }

    public function getQueryset()
    {
        $queryset = $this->model->filter($this->request);
        $queryset = $this->model->appendUserInfo($queryset);
        return $queryset;
    }

    public function performCreate($serializer)
    {
        $instance = parent::performCreate($serializer);
        return $this->model->appendUserInfo($instance);
    }

    public function getForeignPk($default = "")
    {
        return $this->request->variable($this->foreignNameParam, $default);
    }
}
