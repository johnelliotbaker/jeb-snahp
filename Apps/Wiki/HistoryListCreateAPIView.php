<?php

namespace jeb\snahp\Apps\Wiki;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/Wiki/Models/History.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\Wiki\Models\History;

class MySerializer extends Serializer
{
    public function __construct($instance=null, $data=null, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new History();
    }
}


class HistoryListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $paginationClass = 'jeb\snahp\core\Rest\Paginations\PageNumberPagination';
    protected $request;
    protected $sauth;
    protected $foreignNameParam = 'entry';

    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        // $sauth->reject_anon('Error Code: d861e86e82');
        // $this->connectDatabase(false);
        $this->model = new History();
        $this->paginator = new $this->paginationClass();
    }

    public function view($id)/*{{{*/
    {
        $this->request->overwrite($this->foreignNameParam, $id);
        return $this->dispatch();
    }/*}}}*/

    public function getQueryset()/*{{{*/
    {
        $queryset = $this->model->filter($this->request);
        // $queryset = $this->model->appendUserInfo($queryset);
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
