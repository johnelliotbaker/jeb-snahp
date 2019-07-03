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
        case 'drn':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->delete_reps_notifications($cfg);
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
            $rep_total = $this->select_rep_total_for_post($post_id, 0);
            $data['forum_id'] = $post_data['forum_id'];
            $data['topic_id'] = $post_data['topic_id'];
            $data['post_subject'] = $post_data['post_subject'];
            $data['rep_total'] = $rep_total;
            $b_notify = $this->is_notify($poster_id);
            if ($b_notify)
            {
                $this->notification->add_notifications(
                    'jeb.snahp.notification.type.reputation', $data
                );
                $this->update_notifications('jeb.snahp.notification.type.reputation', $post_id, $rep_total);
            }
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

    private function update_notifications($notification_type_name, $post_id, $rep_total)/*{{{*/
    {
        // Previous code used delete + insert. Use proper update instead.
        $notification_type_id = (int) $this->notification->get_notification_type_id($notification_type_name);
        $time = time();
        $sql = 'SELECT notification_data FROM ' . NOTIFICATIONS_TABLE . " WHERE item_id={$post_id} AND notification_type_id={$notification_type_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = unserialize($row['notification_data']);
        $data['rep_total'] = $rep_total;
        $data = serialize($data);
        $sql = 'UPDATE ' . NOTIFICATIONS_TABLE . "
            SET notification_read=0, notification_time={$time}, notification_data='{$data}'
            WHERE item_id={$post_id} AND notification_type_id={$notification_type_id}";
        $this->db->sql_query($sql);
    }/*}}}*/

    private function delete_reps_notifications()/*{{{*/
    {
        $this->delete_reputation_notifications();
        $js = new \phpbb\json_response();
        $js->send(['status' => 'success']);
    }/*}}}*/

    public function delete_reputation_notifications()/*{{{*/
    {
        $sql = 'SELECT * FROM ' . 
            NOTIFICATION_TYPES_TABLE . '
            WHERE notification_type_name="jeb.snahp.notification.type.reputation"';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $type_id = $row['notification_type_id'];
        $sql = 'DELETE FROM ' . NOTIFICATIONS_TABLE . '
            WHERE user_id=' . $this->user->data['user_id'] . '
        AND ' . $this->db->sql_in_set('notification_type_id', $type_id);
        $this->db->sql_query($sql);
    }/*}}}*/

    private function is_notify($poster_id)/*{{{*/
    {
        $type_name = 'jeb.snahp.notification.type.reputation';
        $method = 'notification.method.board';
        $sql = 'SELECT * FROM ' . $this->tbl['user_notifications'] .
            " WHERE item_type='{$type_name}' AND user_id={$poster_id} AND method='{$method}'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row && $row['notify']==0)
        {
            return false;
        }
        return true;
    }/*}}}*/

}
