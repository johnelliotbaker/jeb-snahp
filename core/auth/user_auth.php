<?php
namespace jeb\snahp\core\auth;

class user_auth
{
    const CACHE_DURATION = 10;
    const CACHE_DURATION_LONG = 3600;

    protected $db;
    protected $auth;
    public $user;
    protected $container;
    protected $this_user_id;
    public function __construct(
        $db,
        $user,
        $auth,
        $container
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->container = $container;
        $this->this_user_id = (int) $this->user->data['user_id'];
        $this->user_id = $this->userId = (int) $this->user->data['user_id'];
        $this->phpbb_root_path = $container->getParameter('core.root_path');
    }

    public function is_dev_server()
    {
        global $config;
        $servername = $config['server_name'];
        return isset($servername) && $servername=='192.168.2.12';
    }

    public function reject_group($column, $group_id)
    {
        $sql = 'SELECT COUNT(group_id) as count from ' . GROUPS_TABLE .
            " WHERE {$column}=1 AND group_id={$group_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $b = $row && $row['count'] ? true : false;
        if (!$b) {
            trigger_error('Permission Error. Error Code: ca546fad27');
        }
        return true;
    }

    public function reject_bots()
    {
        // $BOT_GID = 6
        $BOTS_GID = 6;
        $gid = $this->user->data['group_id'];
        if (!$gid || $gid == $BOTS_GID) {
            trigger_error('Access to bots has been denied.');
        }
    }

    public function reject_anon()
    {
        $uid = $this->user->data['user_id'];
        if ($uid == ANONYMOUS) {
            trigger_error('You must login before venturing forth.');
        }
    }

    public function reject_new_users($suffix='')
    {
        $groups = [1, 7]; // GUESTS=1, NEWLY_REGISTERED=7
        if (in_array((int) $this->user->data['group_id'], $groups)) {
            throw new \Exception("You do not have the permission to access this page. $suffix");
        }
    }

    public function rejectRestrictedUser()
    {
        if ($this->user->data['snp_restricted']) {
            trigger_error("Your account has been restricted. Error Code: 4c8ad9def6");
        }
    }


    public function is_valid_user_id($user_id)
    {
        $user_id = (int) $user_id;
        $sql = 'SELECT 1 FROM ' . USERS_TABLE . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }

    public function is_admin()
    {
        return $this->auth->acl_gets('a_');
    }

    public function is_only_dev()
    {
        include_once($this->phpbb_root_path . 'includes/functions_user.php');
        // TODO Get better method for checking for developer roles
        $gid_developer = 13;
        $uid_developer = 10414;
        $user_id = $this->user->data['user_id'];
        $b_dev = group_memberships($gid_developer, $user_id, true)
            && $user_id==$uid_developer;
        return $b_dev;
    }

    public function is_dev()
    {
        include_once($this->phpbb_root_path . 'includes/functions_user.php');
        // TODO Get better method for checking for developer roles
        $gid_developer = 13;
        $uid_developer = 10414;
        $user_id = $this->user->data['user_id'];
        $b_dev = group_memberships($gid_developer, $user_id, true)
            && $user_id==$uid_developer;
        return $this->auth->acl_gets('a_', 'm_') || $b_dev;
    }

    public function is_mod()
    {
        return $this->auth->acl_gets('a_', 'm_');
    }

    public function is_self($user_id)
    {
        return $user_id==$this->user->data['user_id'];
    }

    public function is_op($topic_data)
    {
        $poster_id = $topic_data['topic_poster'];
        $user_id = $this->user->data['user_id'];
        return $poster_id == $user_id;
    }

    public function reject_non_dev_or_self($user_id, $append='')
    {
        if (!($this->is_dev() || $this->is_self($user_id))) {
            $ec = $append ? $append : 'Error Code: 63c3a68b37';
            trigger_error(
                "You don't have the permission to access this page. ${ec}"
            );
        }
    }

    public function reject_muted_user($user_id, $mode, $append='')
    {
        if ($mode=='post') {
            $no_topic_msg = [
                'You are muted and cannot create a new topic.',
                'Error Code: 7695d3509e'
            ];
            if ($this->user->data["snp_mute_topic"]) {
                trigger_error(implode(' ', $no_topic_msg));
            }
        } elseif ($mode=='reply') {
            $no_reply_msg = [
                'You are muted and cannot reply to a topic.',
                'Error Code: af6a453392'
            ];
            if ($this->user->data["snp_mute_reply"]) {
                trigger_error(implode(' ', $no_reply_msg));
            }
        }
    }

    public function reject_non_dev_or_op($append='')
    {
        if (!($this->is_dev() || $this->is_self())) {
            $ec = $append ? $append : 'Error Code: 2672a575d9';
            trigger_error(
                "You don't have the permission to access this page. ${ec}"
            );
        }
    }

    public function reject_non_dev($append='')
    {
        if (!$this->is_dev()) {
            $ec = $append ? $append : 'Error Code: 0302f34660';
            trigger_error(
                "You don't have the permission to access this page. ${ec}"
            );
        }
    }

    public function reject_non_admin($append='')
    {
        if (!$this->is_admin()) {
            $ec = $append ? $append : 'Error Code: b1d917f9e3';
            trigger_error("Only administrator may access this page. ${ec}");
        }
    }

    public function reject_non_moderator($append='')
    {
        if (!$this->is_mod()) {
            $ec = $append ? $append : 'Error Code: 0302f34660';
            trigger_error("Only moderators may access this page. ${ec}");
        }
    }

    public function reject_non_group($group_id, $perm_name)
    {
        $sql = 'SELECT 1 FROM ' . GROUPS_TABLE . '
            WHERE group_id=' . $group_id . ' AND
            ' . $perm_name . '=1';
        $result = $this->db->sql_query($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            trigger_error('Your don\'t have the permission to access this page.');
        }
    }

    public function reject_user_not_in_groupset($user_id, $groupset_name)
    {
        if (!$this->user_belongs_to_groupset($user_id, $groupset_name)) {
            trigger_error(
                'You do not have the permission to view this page. Error Code: ad5611c89b'
            );
        }
    }

    public function user_belongs_to_groupset($user_id, $groupset_name)
    {
        if ($user_id===null) {
            $user_id = $this->this_user_id;
        }
        if ($this->is_dev_server()) {
            $groupset = $this->container->getParameter('jeb.snahp.groups')['dev']['set'];
        } else {
            $groupset = $this->container->getParameter('jeb.snahp.groups')['production']['set'];
        }
        include_once($this->phpbb_root_path . 'includes/functions_user.php');
        $user_id_ary = [$user_id];
        if (!array_key_exists($groupset_name, $groupset)) {
            return false;
        }
        $group_id_ary = $groupset[$groupset_name];
        return group_memberships($group_id_ary, $user_id_ary, true);
    }

    public function user_belongs_to_group($user_id, $group_id)
    {
        include_once($this->phpbb_root_path . 'includes/functions_user.php');
        $user_id_ary = [$user_id];
        $group_id_ary = [$group_id];
        return group_memberships($group_id_ary, $user_id_ary, true);
    }

    public function getMaxFromGroupMemberships($userId, $name)
    {
        $name = $this->db->sql_escape((string) $name);
        $groups = $this->get_user_groups($userId);
        $inset = $this->db->sql_in_set('group_id', $groups);
        $sql = "SELECT MAX(${name}) as max FROM "
            . GROUPS_TABLE . ' WHERE ' . $inset;
        $result = $this->db->sql_query($sql, $this::CACHE_DURATION);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return isset($row['max']) ? $row['max'] : 0;
    }

    public function getMinFromGroupMemberships($userId, $name)
    {
        $name = $this->db->sql_escape((string) $name);
        $groups = $this->get_user_groups($userId);
        $inset = $this->db->sql_in_set('group_id', $groups);
        $sql = "SELECT MIN(${name}) as min FROM "
            . GROUPS_TABLE . ' WHERE ' . $inset;
        $result = $this->db->sql_query($sql, $this::CACHE_DURATION);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return isset($row['min']) ? $row['min'] : 0;
    }

    public function getUserGroups($user_id)
    {
        return $this->get_user_groups($user_id);
    }

    public function get_user_groups($user_id)
    {
        include_once($this->phpbb_root_path . 'includes/functions_user.php');
        $user_id_ary = [$user_id];
        $group_id_ary = false;
        $rowset = group_memberships($group_id_ary, $user_id_ary);
        return array_map(
            function ($arg) {
                return $arg['group_id'];
            },
            $rowset
        );
    }

    public function userNameToUserId($username)
    {
        $cache_duration = 3600;
        $username_clean = $this->db->sql_escape(utf8_clean_string($username));
        $sql = 'SELECT user_id FROM ' .
            USERS_TABLE . " WHERE username_clean='${username_clean}'";
        $result = $this->db->sql_query($sql, $cache_duration);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? (int) $row['user_id'] : null;
    }

    public function userId2ProfileLink($userId, $username=null, $userColor=null)
    {
        $userId = (int) $userId;
        if ($username === null || $userColor === null) {
            $sql = 'SELECT username, user_colour FROM ' . USERS_TABLE . " WHERE user_id=${userId}";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            if (!$row) {
                throw new \Exception("User ${userId} not found: Error Code: 3c4e5cf47e");
            }
            [$username, $userColor] = [$row['username'], $row['user_colour']];
        }
        $profileURL = "/memberlist.php?mode=viewprofile&u=";
        return '<a href="' . $profileURL . '" style="color: ' . $userColor . ';" class="username-coloured">' . $username . '</a>';
    }

}
