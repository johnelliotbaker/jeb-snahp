<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

class analytics extends base
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function handle($mode)
    {
        switch ($mode)
        {
        case 'stats':
            $cfg['tpl_name'] = '@jeb_snahp/analytics/component/stats/base.html';
            $cfg['base_url'] = '/app.php/snahp/analytics/stats/';
            $cfg['title'] = 'Site Statistics';
            return $this->handle_stats($cfg);
        case 'common_thanks':
            $cfg['tpl_name'] = '@jeb_snahp/analytics/common_thanks.html';
            $cfg['base_url'] = '/app.php/snahp/analytics/common_thanks/';
            $cfg['title'] = 'Common Thanks';
            return $this->handle_common_thanks($cfg);
        case 'json_common_thanks':
            $cfg['tpl_name'] = '';
            $cfg['base_url'] = '';
            $cfg['title'] = 'Common Thanks';
            return $this->json_common_thanks($cfg);
        case 'json_user_thanks':
            $cfg['tpl_name'] = '';
            $cfg['base_url'] = '';
            $cfg['title'] = 'User Thanks';
            return $this->json_user_thanks($cfg);
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }

    public function handle_stats($cfg)/*{{{*/
    {
        $this->reject_non_dev();
        $time = (float) microtime();
        $data = $this->select_monthly_stats();
        $blockvar = [];
        foreach ($data as $fieldname => $value)
        {
            $blockvar[$fieldname] = $value;
        }
        $this->template->assign_block_vars('postrow', $blockvar);
        $elapsed_time = (float) microtime() - $time;
        $elapsed_time = sprintf('%f', $elapsed_time);
        $this->template->assign_vars([
            'ELAPSED_TIME' => $elapsed_time,
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp Analytics - Statistics');
    }/*}}}*/

    public function get_or_reject_bump_data($tid)/*{{{*/
    {
        if (!$tid)
        {
            trigger_error('No topic_id was provided.');
        }
        $bump_data = $this->select_bump_topic($tid);
        if (!$bump_data)
        {
            trigger_error('Bump data does not exist.');
        }
        return $bump_data;
    }/*}}}*/

    public function get_or_reject_topic_data($tid)/*{{{*/
    {
        if (!$tid)
        {
            trigger_error('No topic_id was provided.');
        }
        $topicdata = $this->select_topic($tid);
        if (!$topicdata)
        {
            trigger_error('That topic does not exist.');
        }
        return $topicdata;
    }/*}}}*/

    public function deserialize_tid($strn)/*{{{*/
    {
        preg_match_all('#(\d+)#', $strn, $matches);
        $data = [];
        if ($matches)
        {
            return array_unique($matches[1]);
        }
        return $data;
    }/*}}}*/

    public function json_user_thanks($cfg)/*{{{*/
    {
        $this->reject_anon();
        $group_id = $this->user->data['group_id'];
        $this->reject_non_group($group_id, 'snp_ana_b_enable');
        // Get data
        $user_id = $this->request->variable('u', 0);
        $a_tid = $this->request->variable('t', '');
        $a_tid = $this->deserialize_tid($a_tid);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $where = $this->db->sql_in_set('topic_id', $a_tid);
        $sql = 'SELECT * FROM ' . $tbl['thanks'] .
            " WHERE user_id={$user_id} AND $where ORDER BY topic_id";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        foreach ($rowset as &$row)
        {
            $row['thanks_time'] = $this->user->format_date($row['thanks_time']);
            $topic_id = (int) $row['topic_id'];
            $sql = 'SELECT topic_title FROM ' . TOPICS_TABLE .
                " WHERE topic_id=${topic_id}";
            $result = $this->db->sql_query($sql);
            $r = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            $row['topic_title'] = $r['topic_title'];
        }
        $js = new \phpbb\json_response();
        $js->send($rowset);
    }/*}}}*/

    public function json_common_thanks($cfg)/*{{{*/
    {
        $this->reject_anon();
        $group_id = $this->user->data['group_id'];
        $this->reject_non_group($group_id, 'snp_ana_b_enable');
        // Get data
        $a_tid = $this->request->variable('t', '');
        $a_tid = $this->deserialize_tid($a_tid);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        // $a_tid = [387900, 387898, 387894];
        // Analyze and Aggregate
        $aggro = [];
        foreach ($a_tid as $tid)
        {
            $sql = 'SELECT user_id FROM ' . $tbl['thanks'] . 
                " WHERE topic_id={$tid}";
            $result = $this->db->sql_query($sql);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            foreach ($rowset as $row)
            {
                $user_id = $row['user_id'];
                if (array_key_exists($user_id, $aggro))
                {
                    array_push($aggro[$user_id], $tid);
                }
                else
                {
                    $aggro[$user_id] = [$tid];
                }
            }
        }
        arsort($aggro);
        $count = 1;
        $data = [];
        foreach ($aggro as $user_id => $a_tid)
        {
            if ($count > 200) break;
            $sql = 'SELECT * FROM ' . USERS_TABLE .
                " WHERE user_id={$user_id}";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            $username_link = $this->make_username($row);
            $topic_links = [];
            foreach($a_tid as $tid)
            {
                $topic_links[] = "<a href='/viewtopic.php?t={$tid}'>{$tid}</a>";
            }
            $topic_links = implode(' ', $topic_links);
            $entry = [
                'id' => $count,
                'user_id' => $user_id,
                'n_thanks' => count($a_tid),
                'username_link' => $username_link,
                'topic_links' => $topic_links,
            ];
            $data[] = $entry;
            $count += 1;
        }
        $js = new \phpbb\json_response();
        $js->send($data);
    }/*}}}*/

    public function handle_common_thanks($cfg)/*{{{*/
    {
        $this->reject_anon();
        $group_id = $this->user->data['group_id'];
        $this->reject_non_group($group_id, 'snp_ana_b_enable');
        return $this->helper->render($cfg['tpl_name'], 'Snahp Analytics - Common Thanks');
    }/*}}}*/


    private function select_monthly_stats()/*{{{*/
    {
        $n_unique_visitor = $this->get_monthly_unique_users();
        $data = [
            'NAME' => 'N_UNIQUE_VISITOR',
            'DESCRIPTION' => 'Number of unique users visited in the last 30 days.',
            'VALUE' => $n_unique_visitor,
        ];
        return $data;
    }/*}}}*/

    private function get_monthly_unique_users()/*{{{*/
    {
        $time = time();
        $since = (int) ($time - 2592000);
        $where = "u.user_id > 1 AND (u.user_lastvisit > {$since} OR s.session_time > {$since})";
        $sql_ary = [
            'SELECT' => 'u.user_id',
            'FROM' => [USERS_TABLE => 'u'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [SESSIONS_TABLE => 's'],
                    'ON' => 'u.user_id=s.session_user_id',
                ],
            ],
            'WHERE' => $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        $sql = "SELECT COUNT(*) as count FROM ({$sql}) as t";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row['count'];
    }/*}}}*/

}
