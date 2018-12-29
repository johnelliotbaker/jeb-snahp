<?php

use \Symfony\Component\HttpFoundation\Response;

namespace jeb\snahp\controller;

use phpbb\user;
use phpbb\auth\auth;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class admin
{
	protected $u_action;
    protected $user;
    protected $auth;
    protected $request;
    protected $db;
    protected $config;
    protected $helper;
    protected $language;
    protected $template;

    /**
     * Constructor
     *
     * @param \phpbb\config\config      $config
     * @param \phpbb\controller\helper  $helper
     * @param \phpbb\language\language  $language
     * @param \phpbb\template\template  $template
     */
    public function __construct(
        user $user,
        auth $auth,
        request_interface $request,
        driver_interface $db,
        \phpbb\config\config $config,
        \phpbb\controller\helper $helper,
        \phpbb\language\language $language,
        \phpbb\template\template $template
    )
    {
        $this->user     = $user;
        $this->auth     = $auth;
        $this->request  = $request;
        $this->config   = $config;
        $this->helper   = $helper;
        $this->language = $language;
        $this->template = $template;
        $this->db       = $db;
        $this->u_action = 'app.php/snahp/admin/';
    }

    public function handle()
    {
        $data = $this->user->data;
        $auth = $this->auth;
        $request = $this->request;

        if (!$auth->acl_gets('a_'))
        {
            // If not site admin, throw error
            // $auth->acl_gets('a_', 'm_') to include moderators
            trigger_error('Lasciate ogne speranza, voi ch’intrate.');
        }
        // Add security for CSRF
        add_form_key('jeb/snahp');
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('jeb/snahp'))
            {
                trigger_error("Form Key Error");
            }
            // Check form for processing parameters
            $thanks_limit = (int)$request->variable('thanks_limit', '0');
            $hidden_limit = (int)$request->variable('hidden_limit', '0');
            if ($thanks_limit > 0)
            {
                $this->set_thanks($thanks_limit);
            }
            if ($hidden_limit > 0)
            {
                $this->set_hidden($hidden_limit);
            }
            // Show processing output
            if (true)
            {
                meta_refresh(2, $this->u_action);
                $message = "Processed without an error.";
                trigger_error($message);
            }
        }
        return $this->helper->render('snahp_admin.html');
    }

    public function set_hidden($limit)
    {
        $SQL_LIMIT = $limit;
        $sql = 'SELECT topic_id, topic_first_post_id from ' . TOPICS_TABLE . ' ORDER BY topic_id DESC';
        $result = $this->db->sql_query_limit($sql, (int) $SQL_LIMIT);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $pid = $row['topic_first_post_id'];
            $tid = $row['topic_id'];
            $sql_post = 'SELECT post_text from ' . POSTS_TABLE . ' where post_id=' . $pid;
            $result_post = $this->db->sql_query($sql_post);
            $row_post = $this->db->sql_fetchrow($result_post);
            $ptn_hide = '`<s>\[hide\]`';
            if (preg_match($ptn_hide, '`' . $row_post['post_text'] . '`'))
            {
                $sql_strn = 'UPDATE ' . TOPICS_TABLE . ' SET snp_hidden=1 WHERE topic_id=' . $tid . ';';
                $this->db->sql_query($sql_strn);
            } else {
                $sql_strn = 'UPDATE ' . TOPICS_TABLE . ' SET snp_hidden=0 WHERE topic_id=' . $tid . ';';
                $this->db->sql_query($sql_strn);
            }
        }
        $this->db->sql_freeresult($result);
    }

    public function set_thanks($limit)
    {
        define('THANKS_TABLE', 'phpbb_thanks');
        $SQL_LIMIT = $limit;
        $sql = 'SELECT topic_id from ' . TOPICS_TABLE . ' ORDER BY topic_id DESC';
        $result = $this->db->sql_query_limit($sql, (int) $SQL_LIMIT);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $tid = $row['topic_id'];
            $sql_thanks = 'SELECT count(*) as cnt from ' . THANKS_TABLE . ' where topic_id=' . $tid;
            $result_thanks = $this->db->sql_query($sql_thanks);
            $row_thanks = $this->db->sql_fetchrow($result_thanks);
            if ($row_thanks)
            {
                $cnt = $row_thanks['cnt'];
                $sql_strn = 'UPDATE ' . TOPICS_TABLE . ' SET snp_thanks_count=' . $cnt . ' WHERE topic_id=' . $tid . ';';
                $this->db->sql_query($sql_strn);
            }
        }
        $this->db->sql_freeresult($result);
    }
}
