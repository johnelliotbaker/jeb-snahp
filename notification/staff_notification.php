<?php
namespace jeb\snahp\notification;

class staff_notification extends \phpbb\notification\type\base
{
    protected $notifications_table;
    protected $user_loader;
    protected $phpbb_container;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\language\language $language,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        $phpbb_root_path,
        $php_ext,
        $user_notifications_table,
        $notifications_table,
        \phpbb\user_loader $user_loader,
        $phpbb_container
    ) {
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $user_notifications_table
        );
        $this->notifications_table = $notifications_table;
        $this->user_loader = $user_loader;
        $this->phpbb_container = $phpbb_container;
    }

    public static $notification_option = array(
        'lang' => 'Receive notifications from the staff',
        'group' => 'Notification From Snahp',
    );

    private function get_users_in_group($group_id)
    {
        $group_id = (int) $group_id;
        $sql = 'SELECT user_id FROM ' . USER_GROUP_TABLE . " WHERE group_id=${group_id} AND user_pending=0";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function users_to_query()
    {
        // This sets which users' data will be retrieved by user_loader
        // It can be used e.g. by user_loader->get_avatar() to load the avatar image
        return [(int) $this->get_data('staff_id')];
    }

    public function get_url()
    {
        $forum_id = $this->get_data('forum_id');
        $topic_id = $this->get_data('topic_id');
        $post_id  = $this->get_data('post_id');
        $url = "f=${forum_id}&t=${topic_id}&p=${post_id}#p${post_id}";
        return append_sid($this->phpbb_root_path . 'viewtopic.' . $this->php_ext, "$url");
    }

    public function get_avatar()
    {
        $staff_id = $this->get_data('staff_id');
        return $this->user_loader->get_avatar($staff_id);
    }

    public function get_title()
    {
        // This is what's shown on the notification title
        $staff_id = $this->get_data('staff_id');
        $username = $this->user_loader->get_username($staff_id, 'no_profile');
        $group_color = $this->get_data('group_color');
        $group_name = $this->get_data('group_name');
        return "{$username} to <span style='color:{$group_color}; font-weight:900;'>{$group_name}</span>";
    }

    public function get_reference()
    {
        // This is what's shown on the notification text
        return $this->get_data('topic_title');
    }

    public function get_type()
    {
        return 'jeb.snahp.notification.type.staff_notification';
    }

    public function find_users_for_notification($data, $options = array())
    {
        $group_id = $data['group_id'];
        $rowset = $this->get_users_in_group($group_id);
        // Ignore user notification settings.
        // Send as board notification.
        $a_users = [];
        foreach ($rowset as $row) {
            $a_users[$row['user_id']] = $row['user_id'];
        }
        $a_users = $this->check_user_notification_options($a_users);
        $options = [];
        foreach ($a_users as $user_id => $user_methods) {
            if (count($user_methods) > 0) {
                foreach ($user_methods as $method) {
                    if ($method == 'notification.method.board') {
                        $options[$user_id] = [$method];
                    }
                }
            }
        }
        return $options;
    }

    public static function get_item_id($data)
    {
        return (int) $data['post_id'];
    }

    public function is_available()
    {
        return true;
    }

    public static function get_item_parent_id($data)
    {
        return (int) $data['post_id'];
    }

    public function get_redirect_url()
    {
        return $this->get_url();
    }

    public function get_email_template()
    {
        // jeb/snahp/language/en/email/digg_notification_email.txt
        return '@jeb_snahp/staff_notification';
    }

    public function get_email_template_variables()
    {
        // $username = $this->user_loader->get_username($this->get_data('poster_id'), 'username');
        return [];
    }

    public function create_insert_array($data, $pre_create_data = array())
    {
        // This gets written in the "notification_data" column of the
        // notification table after serialization
        // The data is later grabbed using $this->get_data('name');
        $this->set_data('staff_id', $data['staff_id']);
        $this->set_data('forum_id', $data['forum_id']);
        $this->set_data('topic_id', $data['topic_id']);
        $this->set_data('post_id', $data['post_id']);
        $this->set_data('group_id', $data['group_id']);
        $this->set_data('group_color', $data['group_color']);
        $this->set_data('group_name', $data['group_name']);
        $this->set_data('topic_title', $data['topic_title']);
        $this->set_data('post_time', $data['post_time']);
        return parent::create_insert_array($data, $pre_create_data);
    }
}
