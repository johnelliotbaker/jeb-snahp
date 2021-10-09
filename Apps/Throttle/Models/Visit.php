<?php

namespace jeb\snahp\Apps\Throttle\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

class Visit extends Model
{
    const MAX_COUNT = 20;
    const TABLE_NAME = "phpbb_snahp_throttle_visit";

    protected $fields;
    protected $requiredFields = [
        "user",
        "data"
    ];

    public function __construct($User)
    {
        parent::__construct();
        $this->fields = [
            "user" => new IntegerField(["default" => 0]),
            "data" => new StringField(["default" => '']),
        ];
        $this->User = $User;
    }

    public function validateVisitData($instance)
    {
        if (!$instance) {
            return false;
        }
        $data = unserialize($instance->data);
        if (!is_array($data)) {
            return false;
        }
        return count($data) === $this::MAX_COUNT;
    }

    public function updateOrCreateTimestamp($userId, $url='')
    {
        $instance = $this->getObject('user=?', [$userId]);
        if (!$this->validateVisitData($instance)) {
            if ($instance) {
                $this->delete($instance);
            }
            $instance = $this->createTimestamp($userId);
        }
        $data = unserialize($instance->data);
        $entry = ['t' => time(), 'u' => $url];
        $newData = array_merge([$entry], array_slice($data, 0, $this::MAX_COUNT-1));
        $this->update($instance, ['data' => serialize($newData)]);
    }

    public function getDefaultData()
    {
        return array_fill(0, $this::MAX_COUNT, ['t'=>0, 'u'=>'']);
    }

    public function createTimestamp($userId)
    {
        $user = $this->User->getOrRaiseException($userId);
        return $this->create([
            'user' => $user['user_id'],
            'data' => serialize($this->getDefaultData()),
        ]);
    }
}
