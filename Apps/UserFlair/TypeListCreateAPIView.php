<?php

namespace jeb\snahp\Apps\UserFlair;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/UserFlair/Models/Type.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\UserFlair\Models\Type;

class MySerializer extends Serializer
{
    public function __construct($instance=null, $data=null, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new Type();
    }
}


class TypeListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;
    protected $sauth;

    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $sauth->reject_non_dev('Error Code: 729077e8ca');
        $this->model = new Type();
    }

    public function view()
    {
        return parent::dispatch();
    }


    public function getQueryset()/*{{{*/
    {
        $data = getRequestData($this->request);
        if (!$data) {
            return array_values(\R::find($this->model::TABLE_NAME));
        }
        foreach ($data as $varname => $value) {
            $sqlAry[] = "${varname}='${value}'";
        }
        $where = implode(' AND ', $sqlAry);
        return array_values(\R::find($this->model::TABLE_NAME, $where));
    }/*}}}*/

    public function create($request)/*{{{*/
    {
        // $this->sauth->reject_non_dev('Error Code: 11e18d40f8');
        return parent::create($request);
    }/*}}}*/
}
