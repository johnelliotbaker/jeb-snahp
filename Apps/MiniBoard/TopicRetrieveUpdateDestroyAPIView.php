<?php
namespace jeb\snahp\Apps\MiniBoard;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/MiniBoard/Models/Topic.php';

use \Symfony\Component\HttpFoundation\JsonResponse;

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
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


class TopicRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;

    public function __construct($request, $sauth)/*{{{*/
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $sauth->reject_anon('Error Code: b28a40899f');
        $this->connectDatabase();
        $this->model = new Topic();
    }/*}}}*/

    public function retrieve($request)/*{{{*/
    {
        $instance = $this->getObject();
        $instance = $this->model->appendUserInfo($instance);
        return new JsonResponse($instance);
    }/*}}}*/
}
