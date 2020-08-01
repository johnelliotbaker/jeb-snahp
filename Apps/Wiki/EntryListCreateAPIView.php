<?php

namespace jeb\snahp\Apps\Wiki;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/Wiki/Models/Entry.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\Wiki\Models\Entry;

class MySerializer extends Serializer
{
    public function __construct($instance=null, $data=null, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new Entry();
    }
}


class EntryListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;
    protected $sauth;

    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        // $sauth->reject_anon('Error Code: d861e86e82');
        $this->connectDatabase(false);
        $this->model = new Entry();
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

    // public function create($request)/*{{{*/
    // {
    //     $this->sauth->reject_non_dev('Error Code: 11e18d40f8');
    //     return parent::create($request);
    // }/*}}}*/
}
