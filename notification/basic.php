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

	public function get_type()
	{
		return 'jeb.snahp.notification.type.basic';
	}

	public function is_available()
	{
		return true;
	}

	public static function get_item_id($data)
	{
		return (int) $data['post_id'];
	}

	public static function get_item_parent_id($data)
	{
		return (int) $data['topic_id'];
	}

	public function find_users_for_notification($data, $options = array())
	{
		$options = array_merge(array('ignore_users' => array()), $options);

		$users = array((int) $data['poster_id']);
		return $this->check_user_notification_options($users, $options);
	}

	public function get_avatar()
	{
        return $this->user_loader->get_avatar($this->get_data('user_id'));
	}

	public function get_title()
	{
        $username = $this->user_loader->get_username($this->get_data('user_id'), 'no_profile');
        return $this->user->lang($this->language_key, $username);
	}

	public function users_to_query()
	{
		$users = array( $this->get_data('user_id'),);
		return $users;
	}

	public function get_url()
	{
		return append_sid($this->phpbb_root_path . 'viewtopic.' . $this->php_ext, "p={$this->item_id}#p{$this->item_id}");
	}

	public function get_redirect_url()
	{
		return $this->get_url();
	}

	public function get_email_template()
	{
        // jeb/snahp/language/en/email/basic_notification_email.txt
		return '@jeb_snahp/basic_notification_email';
	}

	public function get_reference()
	{
		return $this->user->lang('NOTIFICATION_REFERENCE', censor_text($this->get_data('post_subject')));
	}

	public function get_email_template_variables()
	{
		$username = $this->user_loader->get_username($this->get_data('poster_id'), 'username');
		return array(
			'REPORT_SUBJECT_OP'    => htmlspecialchars_decode($this->user->lang['REPORT_SUBJECT_OP' . $this->get_data('lang_act')]),
			'USERNAME'             => htmlspecialchars_decode($this->user->data['username']),
			'POST_SUBJECT'         => htmlspecialchars_decode(censor_text($this->get_data('post_subject'))),
			'POSTER_NAME'          => htmlspecialchars_decode($username),

            'NOTIFICATION_SUBJECT' => htmlspecialchars_decode($this->get-title()),
            'U_LINK'               => generate_board_url() . '/viewtopic.' . $this->php_ext . "?p={$this->item_id}#p{$this->item_id}",

		);
	}

    public function create_insert_array($data, $pre_create_data = array())
	{
        $this->set_data('user_id', $data['user_id']);
        $this->set_data('post_id', $data['post_id']);
        $this->set_data('post_subject', $data['post_subject']);
        return parent::create_insert_array($data, $pre_create_data);
	}

}
