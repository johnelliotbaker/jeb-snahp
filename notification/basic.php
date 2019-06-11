<?php
namespace jeb\snahp\notification;

class basic extends \phpbb\notification\type\base
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
    )
	{
        parent::__construct(
            $db,
            $language,
            $user,
            $auth,
            $phpbb_root_path,
            $php_ext,
            $user_notifications_table);
		$this->notifications_table = $notifications_table;
		$this->user_loader = $user_loader;
	}

    public static $notification_option = array(
        'lang' => 'Receive notifications from Snahp',
        'group' => 'Notification From Snahp',
    );

	public function users_to_query()
	{
        // prn("users_to_query");
        // trigger_error("error");
		$users = array( $this->get_data('user_id'),);
		return $users;
	}

	public function get_url()
	{
        // prn("get_url");
        // trigger_error("error");
        $topic_id = $this->get_data('topic_id');
		return append_sid($this->phpbb_root_path . 'viewtopic.' . $this->php_ext, "t={$topic_id}&p={$this->item_id}#p{$this->item_id}");
	}

	public function get_avatar()
	{
        // prn("get_avatar");
        // trigger_error("error");
        return $this->user_loader->get_avatar($this->get_data('user_id'));
	}

	public function get_title()
	{
        // prn("get_title");
        // trigger_error("error");
        $username = $this->user_loader->get_username($this->get_data('user_id'), 'no_profile');
        return $this->user->lang($this->language_key, $username);
	}

	public function get_reference()
	{
        // prn("get_reference");
        // trigger_error("error");
		return $this->user->lang('NOTIFICATION_REFERENCE', censor_text($this->get_data('post_subject')));
	}

	public function get_type()
	{
        // prn("get_type");
        // trigger_error("error");
		return 'jeb.snahp.notification.type.basic';
	}

	public function find_users_for_notification($data, $options = array())
	{
        // prn("find_users_for_notification");
        // trigger_error("error");
		$options = array_merge(array('ignore_users' => array()), $options);
		$users = array((int) $data['poster_id']);
		$options =  $this->check_user_notification_options($users, $options);
        foreach ($options as $key => $entry)
        {
            $options[$key] = ['notification.method.board'];
        }
        return $options;
	}

	public static function get_item_id($data)
	{
        // prn("get_item_id");
        // trigger_error("error");
		return (int) $data['post_id'];
	}

	public function is_available()
	{
        // prn("is_available");
        // trigger_error("error");
		return true;
	}

	public static function get_item_parent_id($data)
	{
        // prn("get_item_parent_id");
        // trigger_error("error");
		return (int) $data['topic_id'];
	}

	public function get_redirect_url()
	{
        // prn("get_redirect_url");
        // trigger_error("error");
		return $this->get_url();
	}

	public function get_email_template()
	{
        // prn("get_email_template");
        // trigger_error("error");
        // jeb/snahp/language/en/email/basic_notification_email.txt
		return '@jeb_snahp/basic_notification_email';
	}

	public function get_email_template_variables()
	{
        // prn("get_email_template_variables");
        // trigger_error("error");
		// $username = $this->user_loader->get_username($this->get_data('poster_id'), 'username');
		return array(
			// 'REPORT_SUBJECT_OP'    => htmlspecialchars_decode($this->user->lang['REPORT_SUBJECT_OP' . $this->get_data('lang_act')]),
			// 'USERNAME'             => htmlspecialchars_decode($this->user->data['username']),
			// 'POST_SUBJECT'         => htmlspecialchars_decode(censor_text($this->get_data('post_subject'))),
			// 'POSTER_NAME'          => htmlspecialchars_decode($username),
            //
            // 'NOTIFICATION_SUBJECT' => htmlspecialchars_decode($this->get_title()),
            // 'U_LINK'               => generate_board_url() . '/viewtopic.' . $this->php_ext . "?p={$this->item_id}#p{$this->item_id}",
		);
	}

    public function create_insert_array($data, $pre_create_data = array())
	{
        // prn("create_insert_array");
        // trigger_error("error");
        $this->set_data('topic_id',     $data['topic_id']);
        $this->set_data('user_id',      $data['user_id']);
        $this->set_data('post_id',      $data['post_id']);
        $this->set_data('post_subject', $data['post_subject']);
        $this->set_data('type',         $data['type']);
        return parent::create_insert_array($data, $pre_create_data);
	}

}
