<?php
namespace jeb\snahp\core\bank;

use jeb\snahp\core\invite_helper;

class user_account
{
    protected $db;
    protected $user;
    protected $container;
    protected $sauth;
    protected $bank_transaction_logger;
    protected $exchange_rates;
    protected $invite_helper;

    public function __construct(
        $db,
        $user,
        $container,
        $sauth,
        $bank_transaction_logger,
        $exchange_rates
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->container = $container;
        $this->sauth = $sauth;
        $this->bank_transaction_logger = $bank_transaction_logger;
        $this->exchange_rates = $exchange_rates;

        // TODO: Injecte invite_helper
        global $auth, $config;
        $this->invite_helper = new invite_helper(
            $container,
            $user,
            $auth,
            null,
            $db,
            $config,
            null,
            null
        );
    }

    public function reset($user_id)
    {
        $this->sauth->reject_non_dev('cec49a5984');
        $user_id = (int) $user_id;
        $this->set_balance($user_id, 0);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function get_balance($user_id)
    {
        $user_id = (int) $user_id;
        $sql = 'SELECT snp_bank_n_balance FROM ' . USERS_TABLE . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row) {
            return $row['snp_bank_n_balance'];
        }
        return null;
    }/*}}}*/

    public function set_balance($user_id, $value, $broker_id=-1)
    {
        $user_id = (int) $user_id;
        $value = (int) $value;
        $data = [ 'snp_bank_n_balance' => $value, ];
        $sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . " WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
        $data = [
            'type' => 'moderation',
            'amount' => $value,
            'data' => serialize([]),
        ];
        $b_log = $this->bank_transaction_logger->create_single_transaction($user_id, $broker_id, $data);
        return $b_log;
    }/*}}}*/

    public function create_transaction_and_deposit($amount, $user_id, $broker_id=-1, $comment='')
    {
        $logger = $this->bank_transaction_logger;
        $transaction_id = $logger->create_transaction($user_id, $broker_id);
        $data = [
            'type' => 'deposit',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        $logger->append_to_transaction($transaction_id, $data);
        $this->deposit($amount, $user_id);
        // $b_success = $this->set_balance($user_id, 0);
        return $transaction_id;
    }/*}}}*/

    public function create_transaction_and_withdraw($amount, $user_id, $broker_id=-1, $comment='')
    {
        $transaction_id = $this->create_transaction($user_id, $broker_id);
        $this->withdraw($amount, $transaction_id, $comment);
        return $transaction_id;
    }/*}}}*/

    public function do_exchange($exchange_rate_id, $direction, $amount, $user_id, $broker_id=-1)
    {
        $exchange_rate_id = (int) $exchange_rate_id;
        $direction = $this->db->sql_escape($direction);
        $amount = (int) $amount;
        $exchange_rate = $this->exchange_rates->get_exchange_rate($exchange_rate_id);
        if (!$exchange_rate) {
            return [0, 'Exchange rate does not exist.'];
        }
        if ($direction == 'buy') {
            $rate = $exchange_rate['buy_rate'];
        } elseif ($direction == 'sell') {
            if ($exchange_rate['type'] == 'invitation_points') {
                [$b, $status] = $this->sell_invitation_points($amount, $user_id, $exchange_rate);
                if (!$b) {
                    return [$b, $status];
                }
                $b_log = $this->log_exchange($amount, $user_id, $exchange_rate, $broker_id);
                if ($b_log) {
                    $status .= ' Bank_Transaction_Logger_Success';
                }
            }
        } else {
            return [0, 'Error Code: 2e1f685f1b'];
        }
        return [1, 'Success'];
    }/*}}}*/

    private function sell_invitation_points($amount, $user_id, $exchange_rate)
    {
        global $auth, $config;
        $invite_user_data = $this->invite_helper->select_invite_user($user_id);
        $fund = $invite_user_data && $invite_user_data['n_available'] ? $invite_user_data['n_available'] : 0;
        if (!$invite_user_data || $invite_user_data['n_available'] < $amount) {
            return [0, "You are trying to sell ${amount} invitation points but you only have ${fund} available."];
        }
        $rate = $exchange_rate['sell_rate'];
        $balance_delta = $rate * $amount;
        $broker_id = -1;
        $this->deposit($balance_delta, $user_id);
        $this->decrease_invitation_points($amount, $user_id);
        return [1, 'Success'];
    }/*}}}*/

    public function giveInvitationPoints($amount, $user_id, $type='moderation', $comment='')
    {
        $this->invite_helper->increase_invite_points($user_id, $amount);
        $logger = $this->bank_transaction_logger;
        $amount_formatted = number_format($amount);
        $data = [
            'type' => $type,
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        $this->bank_transaction_logger->create_single_transaction($user_id, $broker_id, $data);
    }/*}}}*/

    public function withdraw($amount, $user_id, $broker_id=-1)
    {
        $amount = (int) $amount;
        $amount = $amount >= 0 ? $amount : 0;
        $balance = $this->get_balance($user_id);
        $balance -= $amount;
        $b_success = $this->set_balance($user_id, $balance);
        return $b_success;
    }/*}}}*/

    public function deposit($amount, $user_id)
    {
        $amount = (int) $amount;
        $amount = $amount >= 0 ? $amount : 0;
        $balance = $this->get_balance($user_id);
        $balance += $amount;
        $b_success = $this->set_balance($user_id, $balance);
        return $b_success;
    }/*}}}*/

    public function log_moderation($amount, $user_id, $broker_id)
    {
        $logger = $this->bank_transaction_logger;
        $amount_formatted = number_format($amount);
        $comment = "Moderation $${amount_formatted}";
        $data = [
            'type' => 'moderation',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        return $logger->create_single_transaction($user_id, $broker_id, $data);
    }/*}}}*/

    public function log_withdraw($amount, $user_id, $broker_id)
    {
        $logger = $this->bank_transaction_logger;
        $amount_formatted = number_format($amount);
        $comment = "Withdraw $${amount_formatted}";
        $data = [
            'type' => 'withdraw',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        return $logger->create_single_transaction($user_id, $broker_id, $data);
    }/*}}}*/

    private function log_exchange($amount, $user_id, $exchange_rate, $broker_id)
    {
        $logger = $this->bank_transaction_logger;
        $transaction_id = $logger->create_transaction($user_id, $broker_id);
        $source_type = $exchange_rate['display_name'];
        $rate = $exchange_rate['sell_rate'];
        $rate_formatted = number_format($exchange_rate['sell_rate']);
        $sell_unit = $exchange_rate['sell_unit'];
        $buy_unit = $exchange_rate['buy_unit'];
        $total = $rate*$amount;
        $total_formatted = number_format($total);
        $comment = "Exchange ${amount} ${source_type} @ ${buy_unit}${rate_formatted}/${sell_unit}";
        $comment = "Sell ${amount} ${source_type} for ${buy_unit}${total_formatted}";
        $data = [
            'type' => 'exchange',
            'amount' => $amount,
            'data' => serialize(['comment' => $comment]),
        ];
        $logger->append_to_transaction($transaction_id, $data);
        $balance_delta = $amount * $rate;
        $balance_delta_formatted = number_format($amount * $rate);
        $comment = "$${balance_delta_formatted} added to the account balance";
        $data = [
            'type' => 'deposit',
            'amount' => $balance_delta,
            'data' => serialize(['comment' => $comment]),
        ];
        return $logger->append_to_transaction($transaction_id, $data);
    }/*}}}*/

    public function decrease_invitation_points($amount, $user_id)
    {
        $this->invite_helper->decrease_invite_points($user_id, $amount);
    }/*}}}*/
}
