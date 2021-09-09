<?php
namespace jeb\snahp\notification;

class simple extends \phpbb\notification\type\base
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

    public static $notification_option = [
        "lang" => "Receive simple notifications",
        "group" => "Notification From Snahp",
    ];

    public function users_to_query()
    {
        return [$this->get_data("sender_id")];
    }

    public function get_url()
    {
        return $this->get_data("link");
    }

    public function get_avatar()
    {
        // Requires data from users_to_query
        return $this->user_loader->get_avatar($this->get_data("sender_id"));
    }

    public function get_title()
    {
        return $this->get_data("title");
    }

    public function get_reference()
    {
        $link = $this->get_data("link");
        $description = $this->get_data("description");
        $reference = '<a href="' . $link . '">' . $description . "</a>";
        return $description;
    }

    public function get_type()
    {
        return "jeb.snahp.notification.type.simple";
    }

    public function find_users_for_notification($data, $options = [])
    {
        $options = array_merge(["ignore_users" => []], $options);
        if (is_array($data["recipient_id"])) {
            $users = $data["recipient_id"];
        } else {
            $users = [(int) $data["recipient_id"]];
        }
        $options = $this->check_user_notification_options($users, $options);
        foreach ($options as $key => $entry) {
            $options[$key] = ["notification.method.board"];
        }
        return $options;
    }

    public static function get_item_id($data)
    {
        return (int) $data["item_id"];
    }

    public function is_available()
    {
        return true;
    }

    public static function get_item_parent_id($data)
    {
        return 0;
    }

    public function get_redirect_url()
    {
        return $this->get_url();
    }

    public function get_email_template()
    {
        return "@jeb_snahp/simple_notification_email";
    }

    public function get_email_template_variables()
    {
        return [];
    }

    public function create_insert_array($data, $pre_create_data = [])
    {
        $mr_robot_user_id = 54;
        // $this->set_data('recipient_id', $data['recipient_id']);
        $this->set_data("sender_id", $mr_robot_user_id);
        $this->set_data("message", $data["message"]);
        $this->set_data("type", $data["type"]);
        $this->set_data("title", $data["title"]);
        $this->set_data("link", $data["link"]);
        $this->set_data("description", $data["description"]);
        return parent::create_insert_array($data, $pre_create_data);
    }
}
