<?php

namespace jeb\snahp\Apps\MiniBoard;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/Apps/MiniBoard/Models/Forum.php";

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\MiniBoard\Models\Forum;

class MySerializer extends Serializer
{
    public function __construct($instance = null, $data = null, $kwargs = [])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new Forum();
    }
}

class ForumRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    protected $serializerClass = __NAMESPACE__ . "\MySerializer";
    protected $request;
    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $sauth->reject_anon("Error Code: d861e86e82");
        $this->model = new Forum();
    }

    public function update($request)
    {
        $this->sauth->reject_non_dev("Error Code: 11e18d40f8");
        return parent::update($request);
    }

    public function delete($request)
    {
        $this->sauth->reject_non_dev("Error Code: 11e18d40f8");
        return parent::delete($request);
    }
}
