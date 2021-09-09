<?php
namespace jeb\snahp\notification;

global $phpbb_root_path;
include_once $phpbb_root_path . "/ext/jeb/snahp/core/functions_utility.php";

class reputation extends \phpbb\notification\type\base
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

    public static $notification_option = [
        "lang" => "Receive reputation notifications",
        "group" => "Notification From Snahp",
    ];

    public function users_to_query()
    {
        // This sets which users' data will be retrieved by user_loader
        // It can be used e.g. by user_loader->get_avatar() to load the avatar image
        return [$this->get_data("poster_id")];
    }

    public function get_url()
    {
        $topic_id = $this->get_data("topic_id");
        $forum_id = $this->get_data("forum_id");
        $post_id = $this->get_data("post_id");
        return append_sid(
            $this->phpbb_root_path . "viewtopic." . $this->php_ext,
            "f={$forum_id}&t={$topic_id}&p={$post_id}#p{$post_id}"
        );
    }

    public function get_avatar()
    {
        return $this->user_loader->get_avatar($this->get_data("poster_id"));
    }

    public function get_title()
    {
        // This is what's shown on the notification title
        $rep_total = $this->get_data("rep_total");
        $strn = "Your post's reputation has risen to {$rep_total}!";
        return $strn;
    }

    public function get_reference()
    {
        // This is what's shown on the notification text
        $topic_title = '"' . $this->get_data("post_subject") . '"';
        return $topic_title;
    }

    public function get_type()
    {
        return "jeb.snahp.notification.type.reputation";
    }

    public function find_users_for_notification($data, $options = [])
    {
        // include_once('/ext/jeb/snahp/core/functions_utility.php');
        $options = [];
        $options[$data["poster_id"]] = ["notification.method.board"];
        return $options;
    }

    public static function get_item_id($data)
    {
        return (int) $data["post_id"];
    }

    public function is_available()
    {
        return true;
    }

    public static function get_item_parent_id($data)
    {
        return (int) $data["topic_id"];
    }

    public function get_redirect_url()
    {
        return $this->get_url();
    }

    public function get_email_template()
    {
        // jeb/snahp/language/en/email/digg_notification_email.txt
        return "@jeb_snahp/digg_notification_email";
    }

    public function get_email_template_variables()
    {
        // $username = $this->user_loader->get_username($this->get_data('poster_id'), 'username');
        return [];
    }

    public function create_insert_array($data, $pre_create_data = [])
    {
        // This gets written in the "notification_data" column of the
        // notification table after serialization
        // The data is later grabbed using $this->get_data('name');
        $this->set_data("forum_id", $data["forum_id"]);
        $this->set_data("topic_id", $data["topic_id"]);
        $this->set_data("rep_total", $data["rep_total"]);
        $this->set_data("post_id", $data["post_id"]);
        $this->set_data("poster_id", $data["poster_id"]);
        $this->set_data("giver_id", $data["giver_id"]);
        $this->set_data("post_subject", $data["post_subject"]);
        return parent::create_insert_array($data, $pre_create_data);
    }
}
