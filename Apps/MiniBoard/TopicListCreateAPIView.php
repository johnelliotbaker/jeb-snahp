<?php

namespace jeb\snahp\Apps\MiniBoard;

require_once 'ext/jeb/snahp/core/Rest/Paginations/PageNumberPagination.php';
require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/MiniBoard/Models/Topic.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\MiniBoard\Models\Topic;

class MySerializer extends Serializer
{
    public function __construct($instance=null, $data=null, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new Topic();
    }
}


class TopicListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $paginationClass = 'jeb\snahp\core\Rest\Paginations\PageNumberPagination';
    protected $request;

    protected $foreignNameParam = 'miniforum';

    public function __construct($request)/*{{{*/
    {
        $this->request = $request;
        $this->connectDatabase();
        $this->model = new Topic();
        $this->paginator = new $this->paginationClass();
    }/*}}}*/

    public function getQueryset()/*{{{*/
    {
        $queryset = $this->model->filter($this->request);
        $queryset = $this->model->appendUserInfo($queryset);
        return $queryset;
    }/*}}}*/

    public function performCreate($serializer)/*{{{*/
    {
        $instance = parent::performCreate($serializer);
        return $this->model->appendUserInfo($instance);
    }/*}}}*/

    public function getForeignPk($default='')/*{{{*/
    {
        return $this->request->variable($this->foreignNameParam, $default);
    }/*}}}*/
}
