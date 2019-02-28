<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

function prn($var, $b_html=false) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v, $b_html); }
    } else {
        if ($b_html)
        {
            echo htmlspecialchars($var) . '<br>';
        }
        else
        {
            echo $var . '<br>';
        }
    }
}

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
        case 'common_thanks':
            $cfg['tpl_name'] = '@jeb_snahp/analytics/common_thanks.html';
            $cfg['base_url'] = '/app.php/snahp/analytics/common_thanks/';
            $cfg['title'] = 'Common Thanks';
            return $this->handle_common_thanks($cfg);
            break;
        case 'json_common_thanks':
            $cfg['tpl_name'] = '';
            $cfg['base_url'] = '';
            $cfg['title'] = 'Common Thanks';
            return $this->json_common_thanks($cfg);
            break;
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }

    public function get_or_reject_bump_data($tid)
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
    }

    public function get_or_reject_topic_data($tid)
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
    }

    public function deserialize_tid($strn)
    {
        preg_match_all('#(\d+)#', $strn, $matches);
        $data = [];
        if ($matches)
        {
            return array_unique($matches[1]);
        }
        return $data;
    }

    public function json_common_thanks($cfg)
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
            if ($count > 50) break;
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
    }

    public function handle_common_thanks($cfg)
    {
        $this->reject_anon();
        $group_id = $this->user->data['group_id'];
        $this->reject_non_group($group_id, 'snp_ana_b_enable');
        return $this->helper->render($cfg['tpl_name'], 'Snahp Analytics - Common Thanks');
    }

}
