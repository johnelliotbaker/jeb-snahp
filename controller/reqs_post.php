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

class reqs_post extends base
{

    public function __construct()
    {
    }

	public function handle($mode, $fid)
	{
		$this->user->add_lang_ext('jeb/snahp', 'common');
		$this->page_title = $this->user->lang('Post Snahp Request');
        $cfg = [];
        switch ($mode)
        {
        case 'tvshows':
            $cfg['tpl_name'] = 'reqs_post_tvshows.html';
            $cfg['b_feedback'] = false;
            $fid = 4;
            return $this->handle_request_form($cfg, $fid);
            break;
        case 'movies':
            $cfg['tpl_name'] = 'reqs_post_movies.html';
            $cfg['b_feedback'] = false;
            $fid = 5;
            return $this->handle_request_form($cfg, $fid);
            break;
        case 'spam':
            $cfg['tpl_name'] = 'reqs_post_movies.html';
            $cfg['b_feedback'] = false;
            $fid = 38;
            return $this->handle_spam($cfg, $fid);
            break;
        case 'thanks':
            $cfg['tpl_name'] = 'reqs_post_movies.html';
            $cfg['b_feedback'] = false;
            $fid = 24;
            return $this->handle_thanks($cfg, $fid);
            break;
        default:
            trigger_error('Invalid request category.');
            break;
        }
	}

    public function send_message($data) {
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    public function handle_thanks()
    {
        $this->reject_non_admin();
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $sql = 'SELECT post_id, topic_id from phpbb_posts order by post_time desc';
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = [];
        $data['user_id']     = $this->user->data['user_id'];
        $data['poster_id']   = 48;
        $data['thanks_time'] = 1549238692;
        $data['forum_id']    = 38;
        $i = 1;
        $topic_id_0 = $row['topic_id'];
        $post_id_0 = $row['post_id'];
        $total = 200000;
        while ($i < $total)
        {
            if ($i % 1000 == 0)
            {
                $data0 = [
                    'status' => 'PROGRESS', 'i' => $i, 'n' => $total,
                    'message' => "$i of $total",
                    'error_message' => 'No Errors',
                    'sqlmsg' => 'insert post',
                ];
                $this->send_message($data0);
            }
            $data['user_id']     = [rand(48, 55), 2][rand(0,1)];
            $data['poster_id'] = rand(48, 55);
            $data['post_id']     = $post_id_0 - $i;
            $data['topic_id']    = $topic_id_0 - $i;
            $sql = 'INSERT INTO phpbb_thanks
                ' . $this->db->sql_build_array('INSERT', $data) . '
                ON DUPLICATE KEY update thanks_time=0';
            $this->db->sql_query($sql);
            $i += 1;
        }
        $data0 = [
            'status' => 'PROGRESS', 'i' => $i, 'n' => $total,
            'message' => "$i of $total",
            'error_message' => 'No Errors',
            'sqlmsg' => 'insert post',
        ];
        $this->send_message($data0);
        $js = new JsonResponse(['status' => "thanked $i times"]);
        return $js;
        // $sql = 'INSERT INTO phpbb_thanks (`post_id`, `poster_id`, `user_id`, `topic_id`, `forum_id`, `thanks_time`) VALUES ' .
        //     ('4367', '2', '48', '3554', '24', '1549237197');
    }

    public function handle_spam($cfg, $fid)
    {
        $this->reject_non_admin();
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $mode = 'post';
        $message = 'spam';
        $subject = 'spam';
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
        $total = 200000;
        while($i < $total)
        {
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

    public function handle_request_form($cfg, $fid)
    {
        global $phpbb_dispatcher;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($this->request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $mode = 'post';
                $message = $this->request->variable('message', '');
                $subject = $this->request->variable('subject', '');
                if (!$subject)
                    trigger_error('Request entry cannot be empty.');
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

                $vars = array('message', 'forum_id',);
                extract($phpbb_dispatcher->trigger_event('jeb.snahp.request_post_submit_before', compact($vars)));
                $message_parser = new \parse_message();
                $message_parser->message = $message ? $message : ' ';
                $message_parser->parse($data_ary['enable_bbcode'], ($this->config['allow_post_links']) ? $data_ary['enable_urls'] : false, $data_ary['enable_smilies'], true, true, true, $this->config['allow_post_links']);
                $message = $message_parser->message;
                $data_ary['message'] = $message;
                $data_ary['message_md5'] = md5($data_ary['message']);
                submit_post($mode, $subject, $username, $topic_type, $poll_ary, $data_ary, $update_message = true, $update_search_index = true);
                meta_refresh(2, $this->u_action);
                trigger_error($this->user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $this->template->assign_vars(array(
                'SNP_B_REQUEST' => $this->config['snp_b_request'],
            ));
            return $this->helper->render($tpl_name, 'title');
        }
    }
}
