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

class mcp extends base
{
    public function __construct()
    {
    }

    public function handle($mode)
    {
        $this->reject_non_moderator();
        $this->user->add_lang_ext('jeb/snahp', 'common');
        $this->page_title = $this->user->lang('mcp');
        $cfg = [];
        switch ($mode) {
        case 'handle_mass_move_v2':
            $cfg['tpl_name'] = "@jeb_snahp/mcp/component/mcp_mass_move_v2/base.html";
            $cfg['base_url'] = "/app.php/snahp/mcp/$mode/";
            $cfg['title'] = 'MCP Mass Mover v2';
            $cfg['b_feedback'] = false;
            return $this->handle_mass_move_v2($cfg);
            break;
        case 'handle_mass_move':
            $cfg['tpl_name'] = '@jeb_snahp/mcp/component/mcp_mass_move/base.html';
            $cfg['base_url'] = '/app.php/snahp/mcp/handle_mass_move/';
            $cfg['title'] = 'MCP Mass Mover';
            $cfg['b_feedback'] = false;
            return $this->handle_mass_move($cfg);
            break;
        default:
            trigger_error('Invalid request category. Error Code: c9299cfd2d');
            break;
        }
    }

    public function send_message($data)
    {/*{{{*/
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }/*}}}*/

    public function select_topics_with_search($per_page, $start, $forum_id=0, $search='')/*{{{*/
    {
        $cache_time = 1;
        $maxi_query = 3000;
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $search = (string) $search;
        $sql = 'SELECT 
                    t.*
                FROM ' . TOPICS_TABLE . " t
                WHERE
                    t.forum_id=$forum_id AND t.topic_title LIKE '%${search}%'
                ORDER BY topic_id DESC";
        // topic_type=0 means regular topics. i.e. not sticky, etc.
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }/*}}}*/

    public function handle_mass_move_v2($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            add_form_key('jeb_snp');
            // IF SUBMITTED
            $search = $this->request->variable('searchterms', '');
            if ($this->request->is_set_post('search')) {
                if (!check_form_key('jeb_snp')) {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
            }
            if ($this->request->is_set_post('submit')) {
                if (!check_form_key('jeb_snp')) {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $vn = $this->request->variable_names();
                $arr = [];
                foreach ($vn as $key) {
                    $b = preg_match('#tid_(\d+)#', $key, $match);
                    if ($b) {
                        $arr[] = (int) $match[1];
                    }
                }
                $to_fid = $this->request->variable('to_forum_id', 0);
                $this->move_to($arr, $to_fid);
            }
            // DEFAULT
            $this->template->assign_var('SEARCH_TERMS', $search);
            // Use cookie to setup navigation settings
            // per page
            $per_page = $this->get_cookie_new('mcp', 'mass_move.per_page');
            $per_page = $per_page ? $per_page : 10;
            $this->template->assign_var("B_SELECT_${per_page}", true);
            // filter topics that has request entry
            $b_request = $this->get_cookie_new('mcp', 'mass_move.b_request');
            $b_request = $b_request ? $b_request : 0;
            $options['b_request'] = $b_request;
            $this->template->assign_var('B_REQUEST', $b_request);
            // Filter request types
            $request_type = $this->get_cookie_new('mcp', 'mass_move.request_type');
            $request_type = $request_type ? $request_type : 'all';
            $options['request_type'] = $request_type;
            $this->template->assign_var("B_SELECT_${request_type}", true);
            // Source forum id
            $from_fid = $this->get_cookie_new('mcp', 'mass_move.from_fid');
            $from_fid = $from_fid ? $from_fid : 0;
            // Source forum id
            $to_fid = $this->get_cookie_new('mcp', 'mass_move.to_fid');
            $to_fid = $to_fid ? $to_fid : 0;
            // Make forum selector
            include_once('includes/functions_admin.php');
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $from_forum_select = make_forum_select(
                $select_id = $from_fid,
                $ignore_id = false,
                $ignore_acl = false,
                $ignore_nonpost = false,
                $ignore_emptycat = true,
                $only_acl_post = false,
                $return_array = false
            );
            $to_forum_select = make_forum_select(
                $select_id = $to_fid,
                $ignore_id = false,
                $ignore_acl = false,
                $ignore_nonpost = false,
                $ignore_emptycat = true,
                $only_acl_post = false,
                $return_array = false
            );
            $this->template->assign_vars(
                [
                    'S_FORUM_SELECT' => $from_forum_select,
                    'S_TO_FORUM_SELECT' => $to_forum_select,
                    'FROM_FORUM_ID' => $from_fid,
                    'TO_FORUM_ID' => $to_fid,
                ]
            );
            // Get topic data
            $start = $this->request->variable('start', 0);
            [$data, $total] = $this->select_topics_with_search(
                $per_page,
                $start,
                $from_fid,
                $search
            );
            $pagination->generate_template_pagination(
                $base_url,
                'pagination',
                'start',
                $total,
                $per_page,
                $start
            );
            foreach ($data as $row) {
                $tid = $row['topic_id'];
                $created_time = $this->user->format_date($row['topic_time']);
                $u_details = "/viewtopic.php?t=$tid";
                $group = array(
                    'TOPIC_TITLE'    => $row['topic_title'],
                    'CREATED_TIME'   => $created_time,
                    'U_VIEW_DETAILS' => $u_details,
                    'TOPIC_ID' => $tid,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

    public function select_moderation_topics($per_page, $start, $forum_id=0, $options=[])/*{{{*/
    {
        $maxi_query = 3000;
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $fid_requests = $this->config['snp_fid_requests'];
        $fid_graveyard = $this->get_fid('graveyard');
        $cache_time = 1;
        $sub = $this->select_subforum($fid_requests, $cache_time);
        $s_requests = join(',', $sub);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        // Filter request specifics
        $cond_request = ' r.tid IS NULL ';
        $cond_request_type = ' 1=1 ';
        $b_request = key_exists('b_request', $options) ? $options['b_request'] : false;
        if ($b_request) {
            $cond_request = ' r.tid IS NOT NULL ';
            $request_type = key_exists('request_type', $options) ? $options['request_type'] : false;
            if ($request_type) {
                $status = key_exists($request_type, $def) ? $def[$request_type] : false;
                if ($status) {
                    $cond_request_type = " r.status=$status ";
                }
            }
        }
        $sql = 'SELECT 
                    t.*
                FROM ' . TOPICS_TABLE . " t
                LEFT OUTER JOIN  
                    {$tbl['req']} r ON (t.topic_id = r.tid)
                WHERE
                    t.forum_id=$forum_id AND $cond_request AND $cond_request_type AND
                    t.topic_type=0
                ORDER BY topic_id DESC";
        // topic_type=0 means regular topics. i.e. not sticky, etc.
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }/*}}}*/

    public function handle_mass_move($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            add_form_key('jeb_snp');
            // IF SUBMITTED
            if ($this->request->is_set_post('graveyard')) {
                if (!check_form_key('jeb_snp')) {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $vn = $this->request->variable_names();
                $arr = [];
                foreach ($vn as $key) {
                    $b = preg_match('#tid_(\d+)#', $key, $match);
                    if ($b) {
                        $arr[] = (int) $match[1];
                    }
                }
                $this->move($arr);
            }
            // DEFAULT
            $options = [];
            $fid_graveyard = $this->get_fid('graveyard');
            $this->template->assign_vars([
                'FID_GRAVEYARD' => $fid_graveyard,
            ]);
            // Use cookie to setup navigation settings
            // per page
            $per_page = $this->get_cookie_new('mcp', 'mass_move.per_page');
            $per_page = $per_page ? $per_page : 10;
            $this->template->assign_vars([
                'B_SELECT_' . $per_page => true,
            ]);
            // filter topics that has request entry
            $b_request = $this->get_cookie_new('mcp', 'mass_move.b_request');
            $b_request = $b_request ? $b_request : 0;
            $options['b_request'] = $b_request;
            $this->template->assign_vars([
                'B_REQUEST' => $b_request,
            ]);
            // Filter request types
            $request_type = $this->get_cookie_new('mcp', 'mass_move.request_type');
            $request_type = $request_type ? $request_type : 'all';
            $options['request_type'] = $request_type;
            $this->template->assign_vars([
                'B_SELECT_' . $request_type => true,
            ]);
            // Source forum id
            $from_fid = $this->get_cookie_new('mcp', 'mass_move.from_fid');
            $from_fid = $from_fid ? $from_fid : 0;
            // Make forum selector
            include_once('includes/functions_admin.php');
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $forum_select = make_forum_select($select_id = $from_fid, $ignore_id = false, $ignore_acl = false, $ignore_nonpost = false, $ignore_emptycat = true, $only_acl_post = false, $return_array = false);
            $this->template->assign_vars([
                'S_FORUM_SELECT' => $forum_select,
            ]);
            // Get topic data
            $start = $this->request->variable('start', 0);
            [$data, $total] = $this->select_moderation_topics($per_page, $start, $from_fid, $options);
            $pagination->generate_template_pagination(
                $base_url,
                'pagination',
                'start',
                $total,
                $per_page,
                $start
            );
            foreach ($data as $row) {
                $tid = $row['topic_id'];
                $created_time = $this->user->format_date($row['topic_time']);
                $u_details = "/viewtopic.php?t=$tid";
                $group = array(
                    'TOPIC_TITLE'    => $row['topic_title'],
                    'CREATED_TIME'   => $created_time,
                    'U_VIEW_DETAILS' => $u_details,
                    'TOPIC_ID' => $tid,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

    public function move_to($a_topic_id, $to_fid)/*{{{*/
    {
        $this->reject_non_moderator();
        include_once('includes/functions_admin.php');
        if (is_array($a_topic_id)) {
            move_topics($a_topic_id, (int) $to_fid, $auto_sync=true);
        }
        return false;
    }/*}}}*/

    public function move($a_topic_id)/*{{{*/
    {
        $this->reject_non_moderator();
        include_once('includes/functions_admin.php');
        $fid_graveyard = $this->get_fid('graveyard');
        if (is_array($a_topic_id)) {
            move_topics($a_topic_id, $fid_graveyard, $auto_sync=true);
        }
        return false;
    }/*}}}*/
}
