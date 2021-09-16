<?php
namespace jeb\snahp\Apps\MrRobot;

// use phpbb\db\driver\driver_interface;
// use jeb\snahp\Apps\Core\Db\QuerySetFactory;
// use jeb\snahp\Apps\Core\Pagination\PageNumberPagination;
// use jeb\snahp\core\auth\user_auth;

class MrRobotHelper
{
    public function __construct($sauth, $User)
    {
        $this->sauth = $sauth;
        $this->User = $User;
        $this->userId = $sauth->userId;
    }

    public function setMrRobotVisibilityInSearch($userId, $value)
    {
        $userId = (int) $userId;
        $value = (int) (bool) $value;
        $this->User->update($userId, ["snp_search_hide_robot" => $value]);
    }
}
