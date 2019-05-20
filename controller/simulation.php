<?php

use \Symfony\Component\HttpFoundation\Response;

namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use jeb\snahp\controller\message_parser;

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include_once($phpbb_root_path . 'common.' . $phpEx);
include_once($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include_once($phpbb_root_path . 'includes/message_parser.' . $phpEx);
// include_once($phpbb_root_path . 'includes/functions_display.' . $phpEx);

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class simulation extends base
{

    public function __construct()
    {
    }

	public function handle($mode)
	{
		$this->user->add_lang_ext('jeb/snahp', 'common');
		$this->page_title = $this->user->lang('Post Snahp Request');
        $cfg = [];
        switch ($mode)
        {
        case 'spam':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            $fid = 24;
            return $this->handle_spam($cfg);
            break;
        case 'move':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->move($cfg);
            break;
        default:
            trigger_error('Invalid request category. Error Code: c9299cfd2d');
            break;
        }
	}


    public function send_message($data) {
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    public function move($cfg)
    {
        $this->reject_non_moderator();
        include_once('includes/functions_admin.php');
        // FORUM DEFINITIONS
        // Find requests forums
        $fid_requests = $this->config['snp_fid_requests'];
        $fid_graveyard = $this->get_fid('graveyard');
        $cache_time = 1;
        $sub = $this->select_subforum($fid_requests, $cache_time);
        $s_requests = join(',', $sub);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = "SELECT 
                    t.topic_id
                FROM
                    phpbb.phpbb_topics t
                LEFT OUTER JOIN
                    phpbb.phpbb_snahp_request r ON (t.topic_id = r.tid)
                WHERE
                    t.forum_id IN ($s_requests) AND r.tid IS NULL
                ORDER BY topic_id DESC";
        $result = $this->db->sql_query_limit($sql, 1000, 0);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $a_topic_id = array_map(function($arr) {
            return $arr['topic_id'];
        }, $rowset);
        if (is_array($a_topic_id))
        {
            move_topics($a_topic_id, $fid_graveyard, $auto_sync=true);
        }
        return false;
    }

    public function handle_spam($cfg)
    {
        $this->reject_non_admin();
        $fid = $this->request->variable('f', 0);
        $n_spam = $this->request->variable('n', 0);
        if (!$fid || !$n_spam)
        {
            return false;
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $mode = 'post';
        $message = 'spam';
        // $subject = 'spam';
        $username = '***Request Username***';
        $topic_type = 0;
        $data_ary = [];
        $data_ary['topic_title'] = 'topic_title';
        $data_ary['icon_id'] = 0;
        $data_ary['forum_id'] = $fid;
        $data_ary['topic_time_limit'] = 0;
        $data_ary['enable_bbcode'] = true;
        $data_ary['enable_smilies'] = true;
        $data_ary['enable_urls'] = true;
        $data_ary['enable_sig'] = true;
        $data_ary['bbcode_bitfield'] = '';
        $bbcode_uid = substr(base_convert(unique_id(), 16, 36), 0, BBCODE_UID_LEN);
        $data_ary['bbcode_uid'] = $bbcode_uid;
        $data_ary['post_edit_locked'] = 0;
        $data_ary['icon_id'] = 0;
        $data_ary['enable_indexing'] = true;
        $data_ary['notify_set'] = false;
        $data_ary['notify'] = false;
        // $vars = array('message', 'forum_id',);
        // $message_parser = new \parse_message();
        // $message_parser->message = $message ? $message : ' ';
        // $message_parser->parse($data_ary['enable_bbcode'], ($this->config['allow_post_links']) ? $data_ary['enable_urls'] : false, $data_ary['enable_smilies'], true, true, true, $this->config['allow_post_links']);
        // $message = $message_parser->message;
        $data_ary['message'] = $message;
        $data_ary['message_md5'] = md5($data_ary['message']);
        $i = 0;
        set_time_limit(0);
        $total = $n_spam;
        while($i < $total)
        {
            $subject = join(' :: ', [(string) $i, uniqid()]);
            submit_post($mode, $subject, $username, $topic_type, $poll_ary, $data_ary, $update_message = true, $update_search_index = true);
            $i += 1;
            if ($i % 1000 == 0)
            {
                $data = [
                    'status' => 'PROGRESS', 'i' => $i, 'n' => $total,
                    'message' => "$i of $total",
                    'error_message' => 'No Errors',
                    'sqlmsg' => 'insert post',
                ];
                $this->send_message($data);
            }
        }
    }

}
