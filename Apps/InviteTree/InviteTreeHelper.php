<?php
namespace jeb\snahp\Apps\InviteTree;

// use phpbb\db\driver\driver_interface;
// use jeb\snahp\Apps\Core\Db\QuerySetFactory;
// use jeb\snahp\Apps\Core\Pagination\PageNumberPagination;
// use jeb\snahp\core\auth\user_auth;

class User
{
    public function __construct($name, $data)
    {
        $this->name = $name;
        $this->children = [];
        $this->root = true;
        $this->depth = 0;
        $this->data = $data;
    }

    public function getDepth()
    {
        if (!$this->children) {
            return 0;
        }
        foreach ($this->children as $child) {
            $depths[] = $child->getDepth();
        }
        return max($depths) + 1;
    }

    public function addChild(User $user)
    {
        $this->children[$user->name] = $user;
    }

    public function str($depth = 0)
    {
        $strn = [];
        $padding = str_repeat("&nbsp;", $depth * 1);
        $color = "#" . $this->data["user_colour"];
        $id = $this->data["id"];
        $name = $this->name == "" ? "Banned User" : $this->name;
        $name = "<span style='color:{$color};'>$name</span>";
        $name = "<a href='https://forum.snahp.it/memberlist.php?mode=viewprofile&u=${id}' style='text-decoration:none;'>$name</a>";
        if ($this->root) {
            $myDepth = $this->getDepth();
            $strn[] = "";
            $strn[] = "$padding $name ($myDepth)";
        } else {
            $strn[] = "$padding $name";
        }
        foreach ($this->children as $child) {
            $strn = array_merge($strn, $child->str($depth + 1));
        }
        return $strn;
    }

    public function __toString()
    {
        return implode("<br/>", $this->str()) . "<br/>";
    }
}

class InviteTreeHelper
{
    public $db;
    public $sauth;
    public $pageNumberPagination;
    public $QuerySetFactory;
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function addNewUser($records, $username, $data)
    {
        if (array_key_exists($username, $records)) {
            return $records;
        }
        $records[$username] = new User($username, $data);
        return $records;
    }

    public function processData($rowset)
    {
        $users = [];
        foreach ($rowset as &$row) {
            $row = array_map("trim", $row);
            $inviterName = $row["inviter"];
            $inviteeName = $row["invitee"];
            if (!$inviteeName) {
                continue;
            }
            $inviterData = [
                "id" => $row["inviter_id"],
                "username" => $row["inviter"],
                "email" => $row["user_email"],
                "user_colour" => $row["inviter_colour"],
            ];
            $inviteeData = [
                "id" => $row["invitee_id"],
                "username" => $row["invitee"],
                "email" => $row["email"],
                "user_colour" => $row["invitee_colour"],
            ];
            $users = $this->addNewUser($users, $inviterName, $inviterData);
            $users = $this->addNewUser($users, $inviteeName, $inviteeData);
            $inviter = $users[$inviterName];
            $invitee = $users[$inviteeName];
            $invitee->root = false;
            $inviter->addChild($invitee);
        }
        ksort($users);
        $users = array_filter($users, function ($user) {
            return $user->root === true && $user->children;
        });
        return $users;
    }

    public function printUsers($users)
    {
        foreach ($users as $user) {
            echo (string) $user;
        }
    }

    public function fromCSV()
    {
        return $this->readCSV();
    }

    public function readCSV()
    {
        $fields = [
            "group_name",
            "n_available",
            "n_total_issued",
            "inviter",
            "inviter_id",
            "inviter_email",
            "inviter_colour",
            "invitee",
            "invitee_id",
            "invitee_email",
            "invitee_colour",
            "user_regdata",
        ];
        $res = [];
        if (
            ($handle = fopen(
                "/var/www/forum/ext/jeb/snahp/Apps/InviteTree/data.csv",
                "r"
            )) !== false
        ) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $tmp = [];
                foreach ($fields as $key => $field) {
                    $tmp[$field] = $data[$key];
                }
                $res[] = $tmp;
            }
        }
        return $res;
    }

    public function fromDatabase()
    {
        $sqlArray = [
            "SELECT" =>
                "d.group_name,e.n_available,e.n_total_issued," .
                "b.username as inviter,a.inviter_id,b.user_email,b.user_colour as inviter_colour," .
                "c.username as invitee,a.redeemer_id,c.user_email as email,c.user_colour as invitee_colour," .
                "FROM_UNIXTIME(c.user_regdate)",
            "FROM" => [$this->tbl["invite"] => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [USERS_TABLE => "b"],
                    "ON" => "a.inviter_id=b.user_id",
                ],
                [
                    "FROM" => [USERS_TABLE => "c"],
                    "ON" => "a.redeemer_id=c.user_id",
                ],
                [
                    "FROM" => [GROUPS_TABLE => "d"],
                    "ON" => "d.group_id=b.group_id",
                ],
                [
                    "FROM" => [$this->tbl["invite_users"] => "e"],
                    "ON" => "e.user_id=b.user_id",
                ],
            ],
            // "WHERE" => "user_id={$user_id}",
            "ORDER_BY" => "c.user_regdate DESC",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }
}
