<?php

namespace jeb\snahp\core\Rest;

require_once 'ext/jeb/snahp/core/Rest/Fields/All.php';

use \R as R;

class Model
{
    protected const FIELDS_NAMESPACE = 'jeb\\snahp\\core\\Rest\\Fields\\';
    protected $requiredFields = [];

    public function __construct()
    {
        global $db, $user;
        $this->db = $db;
        $this->userId = $user->data['user_id'];
    }

    public function getFields()/*{{{*/
    {
        return $this->fields;
    }/*}}}*/

    public function all()/*{{{*/
    {
        return \R::findAll($this::TABLE_NAME, 'LIMIT 500');
    }/*}}}*/

    public function getObject($statement='', $data=[])/*{{{*/
    {
        return \R::findOne($this::TABLE_NAME, $statement, $data);
    }/*}}}*/

    public function getQueryset($statement='', $data=[])/*{{{*/
    {
        return \R::find($this::TABLE_NAME, $statement, $data);
    }/*}}}*/

    public function create($data)/*{{{*/
    {
        foreach ($this->requiredFields as $field) {
            if (!in_array($field, array_keys($data))) {
                throw new MissingRequiredField("${field} is missing. Error Code: 9d2e4f02b5");
            }
        }
        $instance = \R::xdispense($this::TABLE_NAME);
        foreach ($data as $name => $value) {
            $instance->$name = $value;
        }
        $this->performPreCreate($instance);
        \R::store($instance);
        return $instance;
    }/*}}}*/

    public function performPreCreate($instance)/*{{{*/
    {
    }/*}}}*/

    public function update($instance, $data)/*{{{*/
    {
        foreach ($data as $name => $value) {
            $instance->$name = $value;
        }
        \R::store($instance);
        return $instance;
    }/*}}}*/

    public function appendExtra($instance, $extra)/*{{{*/
    {
        foreach ($extra as $key => $value) {
            $instance->$key = $value;
        }
        return $instance;
    }/*}}}*/

    public function appendUserInfo($queryset, $userIdFieldName='author')/*{{{*/
    {
        $cache = [];
        if (gettype($queryset) === 'array') {
            $queryset = array_map(
                function ($arg) {
                    return clone $arg;
                },
                $queryset
            );
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
            $queryset = $this->appendExtra($queryset, $this->getUserInfo($userId));
        }
        return $queryset;
    }/*}}}*/

    public function getUserInfo($userId)/*{{{*/
    {
        global $db;
        $sql = 'SELECT user_avatar, username, user_colour from ' . USERS_TABLE . " WHERE user_id=$userId";
        $result = $db->sql_query($sql, 60);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function getForeignName()/*{{{*/
    {
    }/*}}}*/

    public function wipe()
    {
        R::wipe($this::TABLE_NAME);
    }
}


class MissingRequiredField extends \Exception /*{{{*/
{
}/*}}}*/
