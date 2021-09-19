<?php
namespace jeb\snahp\Apps\UserRestrictor;

class UserRestrictorHelper
{
    protected $db;
    protected $user;
    protected $tbl;
    protected $sauth;
    protected $User;
    public function __construct($db, $user, $sauth, $User)
    {
        $this->db = $db;
        $this->user = $user;
        $this->sauth = $sauth;
        $this->User = $User;
        $this->userId = $sauth->userId;
    }

    public function getRestrictedUsers()
    {
        $fields = ["user_id", "user_colour", "username", "snp_restricted"];
        $data = $this->User->where("snp_restricted=1", ["fields" => $fields]);
        return $data;
    }

    public function restrictUser($userId)
    {
        $this->getUserDataOrThrow($userId);
        $this->User->update($userId, ["snp_restricted" => 1]);
    }

    public function restrictWithUsername($username)
    {
        $data = $this->getUserDataWithUsernameOrThrow($username);
        $userId = $data["user_id"];
        $this->User->update($userId, ["snp_restricted" => 1]);
    }

    public function freeUser($userId)
    {
        $this->getUserDataOrThrow($userId);
        $this->User->update($userId, ["snp_restricted" => 0]);
    }

    public function freeUserWithUsername($username)
    {
        $data = $this->getUserDataWithUsernameOrThrow($username);
        $userId = $data["user_id"];
        $this->User->update($userId, ["snp_restricted" => 0]);
    }

    public function getUserDataWithUsernameOrThrow($username)
    {
        if (!($data = $this->User->getWithUsername($username))) {
            throwHttpException(
                404,
                "$username not found. Error Code: 242306c72d"
            );
        }
        return $data;
    }

    public function getUserDataOrThrow($userId)
    {
        if (!($data = $this->User->get($userId))) {
            throwHttpException(
                404,
                "$userId not found. Error Code: 9292c03ce9"
            );
        }
        return $data;
    }
}
