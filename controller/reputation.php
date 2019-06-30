<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class reputation extends base
{

    protected $base_url = '';

    public function __construct()/*{{{*/
    {
    }/*}}}*/

	public function handle($mode)/*{{{*/
	{
        $this->reject_anon();
        if (!$this->config['snp_rep_b_master'])
        {
            trigger_error('Reputation system has been disabled by the administrator. Error Code: 11f4a36948');
        }
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->user_id = $this->user->data['user_id'];
        switch ($mode)
        {
        case 'add':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->add($cfg);
            break;
        default:
            trigger_error('Invalid update mode. Error Code: bf31942114');
            break;
        }
	}/*}}}*/

    public function add($cfg)/*{{{*/
    {
        $this->add_reputation($cfg);
    }/*}}}*/

    public function add_reputation($cfg)/*{{{*/
    {
        $refresh = 2.5;
        // $this->delete_reputation_table();
        $post_id = $this->request->variable('p', 0);
        $giver_id = $this->user_id;
        if (!$post_id)
        {
            trigger_error('You must provide a valid post id. Error Code: 5797460b62');
        }
        $post_data = $this->select_post($post_id);
        if (!$post_data)
        {
            meta_refresh($refresh, "/viewtopic.php?p={$post_id}#{$post_id}");
            trigger_error('That post does not exist. Error Code: 889ed1c48b');
        }
        $poster_id = $post_data['poster_id'];
        $forum_id = $post_data['forum_id'];
        $topic_id = $post_data['topic_id'];
        if ($poster_id == $giver_id)
        {
            meta_refresh($refresh, "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}");
            trigger_error('You cannot give yourself reputation points. But nice try. Error Code: 18c307e8e6');
        }
        $n_avail = $this->user->data['snp_rep_n_available'];
        if ($n_avail < 1)
        {
            meta_refresh($refresh, "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}");
            trigger_error('You have used all of your reputation points. Error Code: 17d5424110');
        }
        $reputation_data = $this->select_reputation("post_id={$post_id} AND giver_id={$giver_id}");
        if ($reputation_data)
        {
            meta_refresh($refresh, "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}");
            trigger_error('You have already given a reputation point for this post. Error Code: c65721c22f');
        }
        $data = [
            'post_id'   => $post_id,
            'giver_id'  => $giver_id,
            'poster_id' => $poster_id,
            'time'      => time(),
        ];
        $b_success = $this->insert_reputation($data);
        if ($b_success)
        {
            $this->update_giver_on_add($giver_id);
            $this->update_poster_on_add($poster_id);
        }
        $n_avail -= 1;
        meta_refresh($refresh, "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}");
        trigger_error("You now have {$n_avail} reputation points left." );
    }/*}}}*/

    private function select_reputation($where)/*{{{*/
    {
        $where = $this->db->sql_escape($where);
        $sql = 'SELECT * FROM ' . $this->tbl['reputation'] . " WHERE {$where}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    private function select_reputation_count($type='post_id', $id)/*{{{*/
    {
        switch ($type)
        {
        case 'post_id':
            $where = "post_id={$id}";
            break;
        case 'poster_id':
            $where = "poster_id={$id}";
            break;
        case 'giver_id':
            $where = "giver_id={$id}";
            break;
        default:
            $where = "post_id={$id}";
        }
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->tbl['reputation'] . " WHERE {$where}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row)
        {
            return 0;
        }
        return $row['count'];
    }/*}}}*/

    private function insert_reputation($data)/*{{{*/
    {
        $sql = 'INSERT INTO ' . $this->tbl['reputation'] .
            $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return true;
    }/*}}}*/

    private function delete_reputation_table()/*{{{*/
    {
        $sql = 'DELETE FROM ' . $this->tbl['reputation'] . ' WHERE 1=1';
        $this->db->sql_query($sql);
    }/*}}}*/

    private function update_giver_on_add($giver_id)/*{{{*/
    {
        $upd_strn = 'snp_rep_n_given=snp_rep_n_given+1, snp_rep_n_available=snp_rep_n_available-1';
        $sql = 'UPDATE ' . USERS_TABLE . ' SET ' .
            $this->db->sql_escape($upd_strn) .
            " WHERE user_id={$giver_id}";
        $this->db->sql_query($sql);
    }/*}}}*/

    private function update_poster_on_add($poster_id)/*{{{*/
    {
        $upd_strn = 'snp_rep_n_received=snp_rep_n_received+1';
        $sql = 'UPDATE ' . USERS_TABLE . ' SET ' .
            $this->db->sql_escape($upd_strn) .
            " WHERE user_id={$poster_id}";
        $this->db->sql_query($sql);
    }/*}}}*/

}
