<?php
namespace jeb\snahp\notification;

class deadlinks extends \phpbb\notification\type\base
{
    protected $notifications_table;
    protected $user_loader;

    public function __construct(
        \phpbb\db\driver\driver_interface $db,
        \phpbb\language\language $language,
        \phpbb\user $user,
        \phpbb\auth\auth $auth,
        $phpbb_root_path,
        $php_ext,
        $user_notifications_table,
        $notifications_table,
        \phpbb\user_loader $user_loader
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
    }

    public static $notification_option = array(
        'lang' => 'Receive deadlinks notifications',
        'group' => 'Notification From Snahp',
    );

    public function users_to_query()
    {
        return [$this->get_data('userId')];
    }

    public function get_url()
    {
        $topicId = $this->get_data('topicId');
        return append_sid(
            $this->phpbb_root_path . 'viewtopic.' . $this->php_ext,
            "t={$topicId}"
        );
    }

    public function get_avatar()
    {
        return $this->user_loader->get_avatar($this->get_data('userId'));
    }

    public function get_title()
    {
        return $this->get_data('title');
    }

    public function get_reference()
    {
        return '<strong>' . $this->get_data('message') . '</strong>';
    }

    public function get_type()
    {
        return 'jeb.snahp.notification.type.deadlinks';
    }

    public function find_users_for_notification($data, $options = array())
    {
        $options = array_merge(['ignore_users' => []], $options);
        $users = [(int) $data['userId']];
        $options =  $this->check_user_notification_options($users, $options);
        foreach ($options as $key => $entry) {
            $options[$key] = ['notification.method.board'];
        }
        return $options;
    }

    public static function get_item_id($data)
    {
        return (int) $data['topicId'];
    }

    public function is_available()
    {
        return true;
    }

    public static function get_item_parent_id($data)
    {
        return (int) $data['topicId'];
    }

    public function get_redirect_url()
    {
        return $this->get_url();
    }

    public function get_email_template()
    {
        return '@jeb_snahp/basic_notification_email';
    }

    public function get_email_template_variables()
    {
        return [];
    }

    public function create_insert_array($data, $pre_create_data = array())
    {
        $this->set_data('topicId', $data['topicId']);
        $this->set_data('userId', $data['userId']);
        $this->set_data('title', $data['title']);
        $this->set_data('message', $data['message']);
        // $this->set_data('type', $data['type']);
        return parent::create_insert_array($data, $pre_create_data);
    }
}
