<?php

namespace jeb\snahp\Apps\MiniBoard;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/MiniBoard/Models/Forum.php';

use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\Apps\MiniBoard\Models\Forum;

class MySerializer extends Serializer
{
    public function __construct($instance=null, $data=null, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->model = new Forum();
    }
}


class ForumPermissionRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;
    public function __construct($request)
    {
        $this->request = $request;
        $this->connectDatabase();
        $this->model = new Forum();
    }

    public function getObject()/*{{{*/
    {
        global $user;
        $userId = $user->data['user_id'];
        $instance = parent::getObject();
        $data['owner'] = $userId == $instance->owner ? true : false;
        $moderators = explode(',', $instance->moderators);
        $data['moderator'] = in_array($userId, $moderators) ? true : false;
        return $data;
    }/*}}}*/
}
