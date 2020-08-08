<?php

namespace jeb\snahp\Apps\Wiki;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/Wiki/Models/Entry.php';

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
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

class EntryRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;
    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        // $sauth->reject_anon('Error Code: d861e86e82');
        // $this->connectDatabase();
        $this->model = new Entry();
    }

    public function update($request, $partial=false)/*{{{*/
    {
        // $this->sauth->reject_non_dev('Error Code: 11e18d40f8');
        return parent::update($request);
    }/*}}}*/

    public function delete($request)/*{{{*/
    {
        // $this->sauth->reject_non_dev('Error Code: 11e18d40f8');
        return parent::delete($request);
    }/*}}}*/
}
