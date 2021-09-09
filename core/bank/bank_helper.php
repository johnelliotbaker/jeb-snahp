<?php

namespace jeb\snahp\core\bank;
use jeb\snahp\core\invite_helper;


class bank_helper
{


    protected $config;
    protected $db;
    protected $auth;
    protected $template;
    protected $user;
    protected $cache;
/*}}}*/

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\auth\auth $auth,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\cache\driver\driver_interface $cache,
        $phpbb_container,
        $tbl) 
    {
        $this->config = $config;
        $this->db = $db;
        $this->auth = $auth;
        $this->template = $template;
        $this->user = $user;
        $this->cache = $cache;
        $this->container = $phpbb_container;
        $this->tbl_transactions = $tbl['bank_transactions'];
        $this->tbl_transaction_items = $tbl['bank_transaction_items'];
        $this->tbl_exchange_rates = $tbl['bank_exchange_rates'];
        $this->user_id = $user->data['user_id'];
        $this->success = [true, 'Success'];
    }/*}}}*/

    public function get_account_balance($user_id=null)
    {
        $user_id = $user_id===null ? $this->user_id : $user_id;
        $sql = 'SELECT snp_bank_n_token FROM ' . USERS_TABLE . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) return false;
        return $row['snp_bank_n_token'];
    }/*}}}*/

    public function append_exchange_to_transaction($amount, $transaction_id, $comment='')
    {
        $amount = (int) $amount;
        $amount = $amount >= 0 ? $amount : 0;
        $data = [
            'type' => 'exchange',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        $transaction_data = $this->get_transaction($transaction_id);
        if (!$transaction_data) return false;
        $this->append_to_transaction($transaction_id, $data);
    }/*}}}*/

    public function withdraw($amount, $transaction_id, $comment='')
    {
        $amount = (int) $amount;
        $amount = $amount >= 0 ? $amount : 0;
        $data = [
            'type' => 'withdraw',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        $transaction_data = $this->get_transaction($transaction_id);
        if (!$transaction_data) return false;
        $user_id = $transaction_data['user_id'];
        $this->withdraw_token_from_user($user_id, $amount);
        $this->append_to_transaction($transaction_id, $data);
    }/*}}}*/

    public function create_transaction_and_withdraw($amount, $user_id, $broker_id=-1, $comment='')
    {
        $transaction_id = $this->create_transaction($user_id, $broker_id);
        $this->withdraw($amount, $transaction_id, $comment);
        return $transaction_id;
    }/*}}}*/

    public function deposit($amount, $transaction_id, $comment='')
    {
        $amount = (int) $amount;
        $amount = $amount >= 0 ? $amount : 0;
        $data = [
            'type' => 'deposit',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        $transaction_data = $this->get_transaction($transaction_id);
        if (!$transaction_data) return false;
        $user_id = $transaction_data['user_id'];
        $this->deposit_token_to_user($user_id, $amount);
        $this->append_to_transaction($transaction_id, $data);
    }/*}}}*/

    public function create_transaction_and_deposit($amount, $user_id, $broker_id=-1, $comment='')
    {
        $transaction_id = $this->create_transaction($user_id, $broker_id);
        $this->deposit($amount, $transaction_id, $comment);
        return $transaction_id;
    }/*}}}*/

    public function get_exchange_rates($where='1=1', $b_firstrow=false)
    {
        $sql = 'SELECT * FROM ' . $this->tbl_exchange_rates . " WHERE ${where}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        if ($b_firstrow && count($rowset)>0)
        {
            return $rowset[0];
        }
        return $rowset;
    }/*}}}*/

    private function get_exchange_rate($id)
    {
        $id = (int) $id;
        return $this->get_exchange_rates("id=${id}", $b_firstrow=true);
    }/*}}}*/

    private function withdraw_token_from_user($user_id, $amount)
    {
        $amount = (int) $amount;
        $deposit_strn = "snp_bank_n_token=snp_bank_n_token-${amount}";
        $sql = 'Update ' . USERS_TABLE . " SET ${deposit_strn} WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
    }/*}}}*/

    private function deposit_token_to_user($user_id, $amount)
    {
        $amount = (int) $amount;
        $deposit_strn = "snp_bank_n_token=snp_bank_n_token+${amount}";
        $sql = 'Update ' . USERS_TABLE . " SET ${deposit_strn} WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function perform_exchange($exchange_rate_id, $direction, $amount)
    {
        $user_id = $this->user_id;
        $exchange_rate_id = (int) $exchange_rate_id;
        $direction = $this->db->sql_escape($direction);
        $amount = (int) $amount;
        $exchange_rate = $this->get_exchange_rate($exchange_rate_id);
        if (!$exchange_rate) return [0, 'Exchange rate does not exist.'];
        if ($direction == 'buy')
        {
            $rate = $exchange_rate['buy_rate'];
        }
        elseif ($direction == 'sell')
        {
            if ($exchange_rate['type'] == 'invitation_points')
            {
                [$b, $status] = $this->exchange_invitation_points_for_tokens($amount, $user_id, $exchange_rate);
                if (!$b)
                {
                    return [$b, $status];
                }
            }
        }
        else
        {
            return [0, 'Error Code: 2e1f685f1b'];
        }
        return $this->success;
    }/*}}}*/

    private function exchange_invitation_points_for_tokens($amount, $user_id, $exchange_rate)
    {
        $ih = new invite_helper(
            $this->container, $this->user, $this->auth, null,
            $this->db, $this->config, null, null);
        $invite_user_data = $ih->select_invite_user($user_id);
        $fund = $invite_user_data && $invite_user_data['n_available'] ? $invite_user_data['n_available'] : 0;
        if (!$invite_user_data || $invite_user_data['n_available'] < $amount)
        {
            return [0, "Insufficient Invitation Points: ${fund} < ${amount}"];
        }
        $rate = $exchange_rate['sell_rate'];
        $token = $rate * $amount;
        $broker_id = -1;
        $transaction_id = $this->create_transaction_and_deposit(
            $token, $user_id, $broker_id);
        $this->decrease_invitation_points($amount, $user_id);
        $this->append_exchange_to_transaction(
            $amount, $transaction_id, $comment="Exchange invitation points for tokens @ ${rate}.");
        return $this->success;
    }/*}}}*/

    public function decrease_invitation_points($amount, $user_id)
    {
        $ih = new invite_helper(
            $this->container, $this->user, $this->auth, null,
            $this->db, $this->config, null, null);
        $ih->decrease_invite_points($user_id, $amount);
    }/*}}}*/

    public function exchange($type_name, $direction, $amount)
    {
        [$b, $status] = $this->perform_exchange($type_name, $direction, $amount);
    }/*}}}*/

    private function append_to_transaction($transaction_id, $data)
    {
        $data['transaction_id'] = $transaction_id;
        $sql = 'INSERT INTO ' . $this->tbl_transaction_items . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    private function create_transaction($user_id, $broker_id=-1)
    {
        $user_id = (int) $user_id;
        $broker_id = (int) $broker_id;
        if ($user_id < 1) return false;
        $data = [
            'created_time' => time(),
            'user_id' => $user_id,
            'broker_id' => $broker_id,
        ];
        $sql = 'INSERT INTO ' . $this->tbl_transactions . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return (int) $this->db->sql_nextid();
    }/*}}}*/

    public function get_transaction_history($user_id)
    {
        $maxi_query = 10;
        $cooldown = 0;
        $where = "t.user_id=${user_id}";
        $order_by = 't.id DESC';
        $sql_array = [
            'SELECT'	=> '*',
            'FROM'		=> [ $this->tbl_transactions	=> 't', ],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [$this->tbl_transaction_items => 'i'],
                    'ON'	=> 't.id=i.transaction_id',
                ],
            ],
            'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $maxi_query, 0, $cooldown);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        foreach($rowset as &$row)
        {
            $row['created_time_strn'] = $this->user->format_date($row['created_time']);
        }
        return $rowset;
    }/*}}}*/

    private function get_transaction($transaction_id)
    {
        $sql = 'SELECT * from ' . $this->tbl_transactions . " WHERE id={$transaction_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

}
