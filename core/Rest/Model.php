<?php

namespace jeb\snahp\core\Rest;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Fields/All.php";

use \R as R;

use RedBeanPHP\RedException as RedException;

class Model
{
    protected const FIELDS_NAMESPACE = "jeb\\snahp\\core\\Rest\\Fields\\";
    protected $requiredFields = [];
    protected $query = [];

    public function __construct()
    {
        global $db, $user;
        $this->db = $db;
        $this->userId = $user->data["user_id"];
        $this->query = [
            "statement" => "1=1",
            "data" => [],
        ];
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function all()
    {
        return R::findAll($this::TABLE_NAME, "LIMIT 500");
    }

    public function getObject($statement = "", $data = [])
    {
        return R::findOne($this::TABLE_NAME, $statement, $data);
    }

    public function getQueryset($statement = "", $data = [])
    {
        return R::find($this::TABLE_NAME, $statement, $data);
    }

    public function createDummy($data)
    {
        $object = R::xdispense($this::TABLE_NAME);
        foreach ($data as $k => $v) {
            $object->{$k} = $v;
        }
        return $object;
    }

    public function create($data)
    {
        $data = $this->performPreCreate($data);
        foreach ($this->requiredFields as $field) {
            if (!in_array($field, array_keys($data))) {
                throw new MissingRequiredField(
                    "${field} is missing. Error Code: 9d2e4f02b5"
                );
            }
        }
        $instance = R::xdispense($this::TABLE_NAME);
        foreach ($data as $name => $value) {
            $instance->$name = $value;
        }
        try {
            R::store($instance);
        } catch (RedException\SQL $e) {
            throw new RedExceptionSQL($e->getMessage());
        }
        $instance = $this->performPostCreate($instance);
        return $instance;
    }

    public function performPreCreate($data)
    {
        return $data;
    }

    public function performPostCreate($instance)
    {
        return $instance;
    }

    public function update($instance, $data)
    {
        foreach ($data as $name => $value) {
            $instance->$name = $value;
        }
        $this->performPreUpdate($instance);
        R::store($instance);
        $this->performPostUpdate($instance);
        return $instance;
    }

    public function performPreUpdate($instance)
    {
    }

    public function performPostUpdate($instance)
    {
    }

    public function appendExtra($instance, $extra)
    {
        foreach ($extra as $key => $value) {
            $instance->$key = $value;
        }
        return $instance;
    }

    public function appendUserInfo($queryset, $userIdFieldName = "author")
    {
        $cache = [];
        if (gettype($queryset) === "array") {
            $queryset = array_map(function ($arg) {
                return clone $arg;
            }, $queryset);
            foreach ($queryset as $key => $value) {
                $userId = $value->$userIdFieldName;
                if (!array_key_exists($userId, $cache)) {
                    $cache[$userId] = $this->getUserInfo($userId);
                }
                $queryset[$key] = $this->appendExtra($value, $cache[$userId]);
            }
        } else {
            $queryset = clone $queryset;
            $userId = $queryset->$userIdFieldName;
            $queryset = $this->appendExtra(
                $queryset,
                $this->getUserInfo($userId)
            );
        }
        return $queryset;
    }

    public function getUserInfo($userId)
    {
        global $db;
        $sql =
            "SELECT user_id, user_avatar, username, user_colour from " .
            USERS_TABLE .
            " WHERE user_id=$userId";
        $result = $db->sql_query($sql, 60);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }

    public function getForeignName()
    {
    }

    public function getForeignObject($pk)
    {
        return R::load($this::FOREIGN_NAME, $pk);
    }

    public function wipe()
    {
        R::wipe($this::TABLE_NAME);
    }

    public function addUniqueIndex($columns)
    {
        R::getWriter()->addUniqueIndex($this::TABLE_NAME, $columns);
    }

    public function addIndex($name, $column)
    {
        R::getWriter()->addIndex($this::TABLE_NAME, $name, $column);
    }
}

class MissingRequiredField extends \Exception
{
}

class RedExceptionSQL extends \Exception
{
}
