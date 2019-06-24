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
        $this->reject_non_dev();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        if (!$this->config['snp_achi_b_master'])
        {
            trigger_error('Achievement system is currently disabled. Error Code: d39db2d756');
        }
        switch ($mode)
        {
        case 'update':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->update($cfg);
            break;
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
        prn("The update was completed in {$time} seconds.");
        prn($data);
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

}
