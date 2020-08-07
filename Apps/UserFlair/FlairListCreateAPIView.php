<?php

namespace jeb\snahp\Apps\UserFlair;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/UserFlair/Models/Flair.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\UserFlair\Models\Flair;

class MySerializer extends Serializer
{
    public function __construct($instance=null, $data=null, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new Flair();
    }
}


class FlairListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;
    protected $sauth;

    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $sauth->reject_non_dev('Error Code: bb3484c715');
        $this->model = new Flair();
    }

    public function view()
    {
        return parent::dispatch();
    }


    // public function create($request)/*{{{*/
    // {
    //     // $this->sauth->reject_non_dev('Error Code: 11e18d40f8');
    //     return parent::create($request);
    // }/*}}}*/
}
