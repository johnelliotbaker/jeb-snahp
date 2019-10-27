<?php

namespace jeb\snahp\event;/*{{{*/
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;/*}}}*/

class thanks_listener implements EventSubscriberInterface
{
    protected $db;
    protected $user;
    protected $config;
    protected $container;
    protected $tbl;
    protected $sauth;
    protected $data;
    public function __construct(
        $db, $user, $config, $container,
        $tbl,
        $sauth
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->n_allowed_per_cycle = 2;
        $this->cycle_in_seconds = (int) $config['snp_thanks_cycle_duration'];
        $this->data = [];
        $this->sql_cooldown = 0;
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'gfksx.thanksforposts.output_thanks_before'   => 'modify_avatar_thanks',
            'gfksx.thanksforposts.insert_thanks_before'   => 'insert_thanks',
        ];
    }/*}}}*/

    private function get_thanks_user($user_id)/*{{{*/
    {
        $tbl = $this->tbl['thanks_users'];
        $sql = 'SELECT * FROM ' . $tbl . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    private function get_oldest_timestamp($thanks_user_data)/*{{{*/
    {
        $timestrn = $thanks_user_data['timestamps'];
        return (int)explode(',', $timestrn)[0];
    }/*}}}*/

    private function create_thanks_user($user_id)/*{{{*/
    {
        $tbl = $this->tbl['thanks_users'];
        $data = [
            'user_id' => (int) $user_id,
            'b_enable' => 1,
            'timestamps' => '',
        ];
        $sql = 'INSERT INTO ' . $tbl . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        $this->reset_user_timestamps($user_id);
        return $this->get_thanks_user($user_id);
    }/*}}}*/

    private function get_or_create_thanks_user($user_id)/*{{{*/
    {
        $row = $this->get_thanks_user($user_id);
        if (!$row)
        {
            $row = $this->create_thanks_user($user_id);
            if (!$row)
            {
                trigger_error('Could not create thanks user. Error Code: 39658ba274');
            }
        }
        return $row;
    }/*}}}*/

    public function reset_user_timestamps($user_id)/*{{{*/
    {
        $row = $this->get_or_create_thanks_user($user_id);
        $timestamps = $row['timestamps'];
        $n_allowed = $this->n_allowed_per_cycle;
        $a_time = array_fill(0, $n_allowed, 0);
        $timestrn = implode(',', $a_time);
        $data = ['timestamps' => $timestrn];
        $tbl = $this->tbl['thanks_users'];
        $sql = 'UPDATE ' . $tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    private function validate_or_reset_user_timestamps($user_id, $data=null)/*{{{*/
    {
        if ($data===null)
        {
            $data = $this->get_thanks_user($user_id);
        }
        $n_buffer = $this->n_allowed_per_cycle;
        $timestrn = $data['timestamps'];
        $a_time = explode(',', $timestrn);
        if (count($a_time) != $n_buffer || !is_numeric($a_time[0]))
        {
            $this->reset_user_timestamps($user_id);
            return false;
        }
        return true;
    }/*}}}*/

    private function get_cycle_limit($user_id)/*{{{*/
    {
        $groups = $this->sauth->get_user_groups($user_id);
        $inset = $this->db->sql_in_set('group_id', $groups);
        $sql = 'SELECT MAX(tfp_n_per_cycle) as max FROM ' . GROUPS_TABLE . ' WHERE ' . $inset;

        $result = $this->db->sql_query($sql, $this->sql_cooldown);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row)
        {
            return (int) $row['max'];
        }
        return 0;
    }/*}}}*/

    private function setup_thanks_user($user_id)/*{{{*/
    {
        $this->n_allowed_per_cycle = $this->get_cycle_limit($user_id);
        $row = $this->get_or_create_thanks_user($user_id);
        if (!$this->validate_or_reset_user_timestamps($user_id, $row))
        {
            $row = $this->get_thanks_user($user_id);
        }
        $this->data = $row;
    }/*}}}*/

    private function reject_banned_user($user_id)/*{{{*/
    {
        $b_exception = $this->sauth->is_dev();
        if ($b_exception)
        {
            return false;
        }
        if (!$this->data['b_enable'])
        {
            trigger_error('You are banned from using the thanks system. Error Code: 1c13b9cea3');
        }
    }/*}}}*/

    private function reject_excessive_thanks_per_cycle($thanks_user_data)/*{{{*/
    {
        // $b_exception = $this->sauth->is_dev();
        // if ($b_exception)
        // {
        //     return false;
        // }
        if ($this->n_allowed_per_cycle < 1)
        {
            trigger_error('You cannot give any more thanks.');
        }
        $curr = time();
        $oldest = $this->get_oldest_timestamp($thanks_user_data);
        $allowed_after = $oldest + $this->cycle_in_seconds;
        if ($curr < $allowed_after)
        {
            $datestrn = $this->user->format_date($allowed_after);
            trigger_error('You cannot give any more thanks until ' . $datestrn);
        }
    }/*}}}*/

    private function insert_timestamp($user_id)/*{{{*/
    {
        $tbl = $this->tbl['thanks_users'];
        $timestrn = $this->data['timestamps'];
        $a_time = explode(',', $timestrn);
        $a_time = array_slice($a_time, 1);
        $a_time[] = time();
        $timestrn = implode(',', $a_time);
        $data = ['timestamps' => $timestrn];
        $sql = 'UPDATE ' . $tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function insert_thanks($event)/*{{{*/
    {
        if (!$this->config['snp_thanks_b_enable']) return false;
        $from_id = $event['from_id'];
        $to_id = $event['to_id'];
        if ($this->config['snp_thanks_b_limit_cycle'])
        {
            $this->setup_thanks_user($from_id);
            $this->reject_banned_user($from_id);
            $this->reject_excessive_thanks_per_cycle($this->data);
        }
        $sql = 'UPDATE ' . USERS_TABLE . ' SET snp_thanks_n_given=snp_thanks_n_given+1 WHERE user_id=' . $from_id;
        $this->db->sql_query($sql);
        $sql = 'UPDATE ' . USERS_TABLE . ' SET snp_thanks_n_received=snp_thanks_n_received+1 WHERE user_id=' . $to_id;
        $this->db->sql_query($sql);
        $this->insert_timestamp($from_id);
    }/*}}}*/

    public function modify_avatar_thanks($event) /*{{{*/
    {
        $poster_id = $event['poster_id'];
        $sql = 'SELECT snp_disable_avatar_thanks_link FROM ' . USERS_TABLE . ' WHERE user_id=' . $poster_id;
        $result = $this->db->sql_query($sql, 30);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $b_disable_avatar_thanks_link = false;
        if ($row)
        {
            $b_disable_avatar_thanks_link= $row['snp_disable_avatar_thanks_link'];
        }
        if ($b_disable_avatar_thanks_link)
        {
            $event['u_receive_count_url'] = false;
            $event['u_give_count_url'] = false;
        }
    }/*}}}*/

}
