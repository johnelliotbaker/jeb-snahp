<?php

namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;
use jeb\snahp\core\topic_mover;

class admin extends base
{
    protected $prefix;
    protected $topic_mover;
    public function __construct( $prefix, topic_mover $topic_mover)
    {
        $this->prefix = $prefix;
        $this->topic_mover = $topic_mover;
        $this->u_action = 'app.php/snahp/admin/';
    }

    protected function get_topic_data(array $topic_ids)
    {
        if (!function_exists('phpbb_get_topic_data'))
        {
            include('includes/functions_mcp.php');
        }

        return phpbb_get_topic_data($topic_ids);
    }

    public function handle_move_single_topic($tid, $fid)
    {
        $this->reject_non_moderator();
        $a_tid = [$tid];
        $td = $this->get_topic_data($a_tid);
        $this->topic_mover->move_topics($td, $fid);
        $data = ['status' => 'SUCCESS'];
        $json = new JsonResponse($data);
        return $json;
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
                trigger_error('Form Key Error');
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
                $message = 'Processed without an error.';
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
