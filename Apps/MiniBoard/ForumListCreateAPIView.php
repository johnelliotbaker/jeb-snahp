<?php
namespace jeb\snahp\Apps\MiniBoard;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/MiniBoard/Models/Forum.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
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


class ForumListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = __NAMESPACE__ . '\MySerializer';
    protected $request;
    protected $sauth;

    public function __construct($request)
    {
        $this->request = $request;
        // $this->userId = (int) $this->user->data['user_id'];
        // $this->sauth->reject_anon('Error Code: a5e8ee80c7');
        $this->connectDatabase();
        $this->model = new Forum();
    }

    public function getQueryset()
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
    }
}
