<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class achievements extends base
{

    protected $base_url = '';

    public function __construct()/*{{{*/
    {
    }/*}}}*/

	public function handle($mode)/*{{{*/
	{
        $this->reject_anon();
        if (!$this->config['snp_achi_b_master'])
        {
            trigger_error('This achievement system has been disabled by the administrator. Error Code: a089bce09c');
        }
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        switch ($mode)
        {
        case 'update':
            $this->reject_non_dev(', d4c39e1065');
            $cfg['tpl_name'] = '@jeb_snahp/mcp/component/mcp_achievements_update/base.html';
            $cfg['b_feedback'] = false;
            return $this->update($cfg);
            break;
        case 'powerchart':
            $cfg = [];
            return $this->get_powerchart_json($cfg);
        default:
            trigger_error('Invalid update mode. Error Code: d3761802f0');
            break;
        }
	}/*}}}*/

    public function update($cfg)/*{{{*/
    {
        $time = microtime(true);
        $type = $this->request->variable('type', '');
        $data = $this->update_achievements($type);
        $time = microtime(true) - $time;
        foreach ($data as $entry)
        {
            $user_data = $this->select_user($entry['user_id']);
            $username = $this->make_username($user_data);
            $entry['username'] = $username;
            $this->template->assign_block_vars('DATA', $entry);
        }
        $this->template->assign_vars([
            'MEMORY_USAGE' => (string) number_format(memory_get_usage()),
            'MEMORY_PEAK_USAGE' => (string) number_format(memory_get_peak_usage()),
            'ELAPSED_TIME' => (string) $time,
            'TITLE' => 'MCP Achievements Updater',
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp Achievements Updater');
        trigger_error('The achievements were updated successfully.');
        // meta_refresh(2, $this->base_url);
        // trigger_error('You are now subscribed to this topic.');
    }/*}}}*/

    public function update_achievements($type)/*{{{*/
    {
        $data = [];
        $data[] = $this->update_magnetic_personality();
        $data[] = $this->update_computer_whiz();
        $data[] = $this->update_grave_digger();
        $data[] = $this->update_life_giver();
        $data[] = $this->update_thumb_raiser();
        $data[] = $this->update_gambler();
        $data[] = $this->update_the_gourmet();
        $data[] = $this->update_intense_training();
        $data[] = $this->update_bone_head();
        $data[] = $this->update_size_matters();
        $data[] = $this->update_child_at_heart();
        $data[] = $this->update_solar_powered();
        foreach ($data as $entry)
        {
            if (!$entry)
            {
                continue;
            }
            if ($entry['unique'])
            {
                $sql = 'DELETE FROM ' . $this->tbl['achievements'] . " WHERE type='{$entry['type']}'";
                $this->db->sql_query($sql);
                unset($entry['unique']);
                unset($entry['value']);
                $sql_ary = $this->db->sql_build_array('INSERT', $entry);
                $sql = 'INSERT INTO ' . $this->tbl['achievements'] . $sql_ary;
                $this->db->sql_query($sql);
            }
        }
        return $data;
    }/*}}}*/

    public function update_magnetic_personality()/*{{{*/
    {
        $sql_ary = [
            'SELECT' =>  'topic_poster, COUNT(*) as count',
            'FROM' => [$this->tbl['digg_slave'] => 's'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [TOPICS_TABLE => 't'],
                    'ON' => 's.topic_id = t.topic_id',
                ],
            ],
            'GROUP_BY' => 't.topic_poster',
            'ORDER_BY' => 'count DESC',
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = [
            'unique' => true,
            'type' => 'magnetic_personality',
            'user_id' => $row['topic_poster'],
            'value' => $row['count'],
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_grave_digger()/*{{{*/
    {
        $sql = 'SELECT user_id, COUNT(*) AS count FROM '. $this->tbl['digg_slave'] . '
                GROUP BY user_id ORDER BY count DESC';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = [
            'unique' => true,
            'type' => 'grave_digger',
            'user_id' => $row['user_id'],
            'value' => $row['count'],
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_computer_whiz()/*{{{*/
    {
        // TODO: Don't hard code this
        $root_fid = 9;
        if ($this->is_dev_server()) { $root_fid = 51; }
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $a_fid = $this->select_subforum($root_fid);
        $include = $this->db->sql_in_set('forum_id', $a_fid);
        $sql = 'SELECT topic_poster, COUNT(*) as count FROM ' . TOPICS_TABLE .
            ' WHERE ' . $include . ' GROUP BY topic_poster ORDER BY count DESC';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = [
            'unique' => true,
            'type' => 'computer_whiz',
            'user_id' => $row['topic_poster'],
            'value' => $row['count'],
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_thumb_raiser()/*{{{*/
    {
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $sql = 'SELECT user_id, snp_thanks_n_received FROM ' . USERS_TABLE . ' ORDER BY snp_thanks_n_received DESC';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = [
            'unique' => true,
            'type' => 'thumb_raiser',
            'user_id' => $row['user_id'],
            'value' => $row['snp_thanks_n_received'],
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_life_giver()/*{{{*/
    {
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $sql = 'SELECT fulfiller_uid, COUNT(*) as count FROM ' . $this->tbl['req'] . 
            ' WHERE status=' . $def['solve'] . ' GROUP BY fulfiller_uid ORDER BY count DESC';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = [
            'unique' => true,
            'type' => 'life_giver',
            'user_id' => $row['fulfiller_uid'],
            'value' => $row['count'],
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_gambler()/*{{{*/
    {
        $user_id = 49485;
        if ($this->is_dev_server())
        {
            $user_id = 2;
        }
        $data = [
            'unique' => true,
            'type' => 'gambler',
            'user_id' => $user_id,
            'value' => 100,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_the_gourmet()/*{{{*/
    {
        $user_id = 5495;
        if ($this->is_dev_server())
        {
            $user_id = 2;
        }
        $data = [
            'unique' => true,
            'type' => 'the_gourmet',
            'user_id' => $user_id,
            'value' => 0,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_solar_powered()/*{{{*/
    {
        $user_id = 0;
        if ($this->is_dev_server())
        {
            $user_id = 0;
        }
        $data = [
            'unique' => true,
            'type' => 'solar_powered',
            'user_id' => $user_id,
            'value' => 0,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_intense_training()/*{{{*/
    {
        $user_id = 58626;
        if ($this->is_dev_server())
        {
            $user_id = 2;
        }
        $data = [
            'unique' => true,
            'type' => 'intense_training',
            'user_id' => $user_id,
            'value' => 0,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_bone_head()/*{{{*/
    {
        $user_id = 150397;
        if ($this->is_dev_server())
        {
            $user_id = 2;
        }
        $data = [
            'unique' => true,
            'type' => 'bone_head',
            'user_id' => $user_id,
            'value' => 0,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_size_matters()/*{{{*/
    {
        $user_id = 49485;
        if ($this->is_dev_server())
        {
            $user_id = 2;
        }
        $data = [
            'unique' => true,
            'type' => 'size_matters',
            'user_id' => $user_id,
            'value' => 0,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function update_child_at_heart()/*{{{*/
    {
        $user_id = 51956;
        if ($this->is_dev_server())
        {
            $user_id = 2;
        }
        $data = [
            'unique' => true,
            'type' => 'child_at_heart',
            'user_id' => $user_id,
            'value' => 0,
            'modified_time' => time(),
        ];
        return $data;
    }/*}}}*/

    public function get_powerchart_json($cfg)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $profile_id = $this->request->variable('u', 0);
        if ($profile_id < 2)
        {
            $js->send([]);
        }
        $stylename = $this->select_style_name();
        $max_solve  = 900;
        $max_rep    = 800;
        $max_thanks = 100000;
        $ref_data = array_map('log', [$max_solve, $max_rep, $max_thanks]);
        $row = $this->select_achievements($profile_id);
        $user_data = array_map('log', [$row['snp_req_n_solve'], $row['snp_rep_n_received'], $row['snp_thanks_n_received']]);
        $user_data = $this->normalize_achievements($user_data, $ref_data);
        $average_data = $this->config['snp_rep_average_data'];
        if (!$average_data)
        {
            $average_data = array_map('log', [0, 0, 4]);
        }
        $average_data = $this->normalize_achievements($average_data, $ref_data);
        $username = $row['username'];
        $maxi = max(max(array_merge($user_data, $average_data)), log(100));
        $data = [
            'stylename' => $stylename,
            'labels' => ['Requests Solved', 'Reputation', 'Thanks'],
            'maximum' => $maxi,
            'user' => [
                'data' => $user_data,
                'name' => $username,
            ],
            'average' => [
                'data' => $average_data,
                'name' => 'Forum Average',
            ]
        ];
        $js->send($data);
        return $data;
    }/*}}}*/

    private function normalize_achievements($data, $reference)/*{{{*/
    {
        $max = log(100);
        $min = log(3.5);
        foreach ($data as $key => $val)
        {
            $data[$key] = min(max(($max * $data[$key] / $reference[$key]), $min), $max);
        }
        return $data;
    }/*}}}*/

    private function select_achievements($user_id)/*{{{*/
    {
        $user_id = (int) $user_id;
        $sql = 'SELECT username, snp_req_n_solve, snp_thanks_n_received, snp_rep_n_received FROM ' . USERS_TABLE .
            " WHERE user_id={$user_id}";
        $result = $this->db->sql_query($sql, 0);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

}
