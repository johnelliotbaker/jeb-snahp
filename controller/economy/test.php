<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;
use jeb\snahp\core\invite_helper;

class test extends base
{
    protected $prefix;
    protected $allowed_groupset;

    public function __construct($prefix, $bank_helper, $market_helper, $user_inventory_helper,
        $bank_user_account, $user_inventory, $product_class
    )
    {
        $this->prefix = $prefix;
        $this->bank_helper = $bank_helper;
        $this->market_helper = $market_helper;
        $this->user_inventory_helper = $user_inventory_helper;
        $this->allowed_groupset = 'Red Team';
        $this->bank_user_account = $bank_user_account;
        $this->user_inventory = $user_inventory;
        $this->product_class = $product_class;
    }/*}}}*/

    public function handle($mode)
    {
        $allowed_groupset = 'Red Team';
        $this->user_id = (int) $this->user->data['user_id'];
        $this->reject_user_not_in_groupset($this->user_id, $allowed_groupset);
        switch ($mode)
        {
        case 'user_manager':
            $cfg['tpl_name'] = '@jeb_snahp/economy/mcp/user_manager/base.html';
            return $this->handle_user_manager($cfg);
        case 'transactions':
            $cfg['tpl_name'] = '@jeb_snahp/bank/component/transactions/base.html';
            return $this->handle_transactions($cfg);
        case 'exchange':
            $cfg = [];
            return $this->handle_exchange($cfg);
        case 'buy_product':
            $cfg = [];
            return $this->handle_buy_product($cfg);
        case 'test':
            $cfg['tpl_name'] = '@jeb_snahp/bank/base.html';
            return $this->handle_test($cfg);
        case 'reset_user':
            return $this->reset_user();
        case 'set_user_balance':
            return $this->set_user_balance();
        case 'process_exchange_confirm':
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    private function set_user_balance()
    {
        $user_id = $this->request->variable('u', 0);
        if (!$user_id)
        {
            return false;
        }
        $balance = $this->request->variable('b', -1);
        if ($balance < 0)
        {
            return false;
        }
        $js = new \phpbb\json_response();
        $this->bank_user_account->set_balance($user_id, $balance);
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }/*}}}*/

    private function reset_user()
    {
        $user_id = $this->request->variable('u', 0);
        if (!$user_id)
        {
            return false;
        }
        $js = new \phpbb\json_response();
        $this->bank_user_account->reset($user_id);
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }/*}}}*/

    private function has_enough_token($total_cost, $user_id)
    {
        $n_token = (int) $this->bank_helper->get_account_balance($user_id);
        if ((int) $total_cost > $n_token)
        {
            return false;
        }
        return true;
    }/*}}}*/

    private function get_total_cost($quantity, $product_class_id)
    {
        return (int) $this->market_helper->get_total_cost($product_class_id, $quantity);
    }/*}}}*/

    private function can_buy($required_quantity, $product_class_id, $user_id)
    {
        $product_class_id = (int) $product_class_id;
        // Check if requested quantity doesn't violate max_per_user
        $prod_data = $this->market_helper->get_product_class($product_class_id);
        if (!$prod_data) return [0, 'That item does not exist.'];
        $max_per_user = $prod_data['max_per_user'];
        $inv_data = $this->user_inventory_helper->get_single_inventory("product_class_id=${product_class_id}" , $user_id);
        $existing_quantity = $inv_data ? $inv_data['quantity'] : 0;
        $purchasable_quantity = $max_per_user - $existing_quantity;
        if ($required_quantity > $purchasable_quantity) return [0, "You cannot purchase more than ${purchasable_quantity}."];
        // Check if there is enough fund
        $total_cost = $this->get_total_cost($required_quantity, $product_class_id);
        if (!$this->has_enough_token($total_cost, $user_id)) return [0, 'Insufficient fund.'];
        return [1, 'Success'];
    }/*}}}*/

    public function handle_buy_product($cfg)
    {
        $js = new \phpbb\json_response();
        $uinv = $this->user_inventory_helper;
        $bank = $this->bank_helper;
        $mk = $this->market_helper;
        $product_class_id = $this->request->variable('pcid', 0);
        $quantity = $this->request->variable('quantity', 0);
        $user_id = $this->request->variable('user_id', 0);
        $user_id = $user_id > 0 ? $user_id : $this->user_id;
        $total_cost = $this->get_total_cost($quantity, $product_class_id);
        [$b_purchasable, $reason] = $this->can_buy($quantity, $product_class_id, $user_id);
        if (!$b_purchasable)
        {
            return $js->send(['status'=> 0, 'reason' => $reason]);
        }
        $b_inv = $uinv->do_add_item($product_class_id, $quantity, $user_id);
        if (!$b_inv)
        {
            return $js->send(['status' => 0, 'reason'=> 'Could not add inventory to the user.']);
        }
        $broker_id = -1;
        $product_class_data = $mk->get_product_class($product_class_id);
        $comment = "Purchasing ${quantity} x ${product_class_data['display_name']} for total price of ${total_cost}";
        $b_bank = $bank->create_transaction_and_withdraw($total_cost, $user_id, $broker_id, $comment);
        if (!$b_bank)
        {
            return $js->send(['status'=> 0, 'reason' => 'Could not process banking transaction.']);
        }
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }/*}}}*/

    public function handle_exchange($cfg)
    {
        $amount = $this->request->variable('amount', 0);
        $exchange_rate_id = $this->request->variable('id', 0);
        $direction = $this->request->variable('dir', 'sell');
        [$b_success, $reason] = $this->bank_helper->perform_exchange($exchange_rate_id, $direction, $amount);
        $data = ['status' => $b_success, 'reason' => $reason];
        $js = new \phpbb\json_response();
        return $js->send($data);
    }/*}}}*/

    public function handle_user_manager($cfg)
    {
        $this->reject_non_dev();
        $time = microtime(true);
        $bua = $this->bank_user_account;
        $user_id = $this->request->variable('u', 0);
        if (!$user_id)
        {
            $this->template->assign_vars([
                'ERROR' => 'That user does not exist. Error Code: 2ee4fe201a',
            ]);
            return $this->helper->render($cfg['tpl_name'], 'Snahp Economy Manager');
        }
        $balance = $bua->get_balance($user_id);
        if ($balance===null)
        {
            trigger_error('Could not get user balance. Error Code: 8d7fa5195e');
        }
        $pclasses = $this->product_class->get_product_classes();
        $inventory = $this->user_inventory->get_inventory($user_id);
        $tmp = [];
        foreach ($inventory as $inv)
        {
            $tmp[$inv['product_class_id']] = $inv;
        } 
        foreach ($pclasses as $pc)
        {
            if (array_key_exists($pc['id'], $tmp))
            {
                $pc['quantity'] = $tmp[$pc['id']]['quantity'];
            }
            else
            {
                $pc['quantity'] = 0;
            }
            $pc['json'] = json_encode($pc);
            $this->template->assign_block_vars('INV', $pc);
        }
        $elapsed_time = microtime(true) - $time;
        $this->template->assign_vars([
            'ELAPSED_TIME' => $elapsed_time,
            'BALANCE' => $balance,
            'BALANCE_FORMATTED' => number_format($balance),
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp test - Statistics');
    }/*}}}*/

    public function handle_transactions($cfg)
    {
        $this->reject_non_dev();
        $time = microtime(true);
        $user_id = $this->user->data['user_id'];
        $n_token = $this->get_token($user_id);
        $transaction_history = $this->bank_helper->get_transaction_history($user_id);
        foreach($transaction_history as $entry)
        {
            $this->template->assign_block_vars('HISTORY', $entry);
        }
        $elapsed_time = microtime(true) - $time;
        $this->template->assign_vars([
            'ELAPSED_TIME' => $elapsed_time,
            'N_TOKEN' => number_format($n_token),
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp test - Statistics');
    }/*}}}*/

    public function handle_test($cfg)
    {
        $this->reject_anon();
        $user_id = $this->user->data['user_id'];
        $this->reject_user_not_in_groupset($user_id, 'Red Team');
        $time = microtime(true);

        $n_token = $this->get_token($user_id);

        $n_avail_inv_pts = $this->get_available_invite_points($user_id);

        $exchange_rates = $this->bank_helper->get_exchange_rates();
        foreach($exchange_rates as $entry)
        {
            $entry['sell_rate_formatted'] = number_format($entry['sell_rate']);
            $entry['json'] = json_encode($entry);
            $this->template->assign_block_vars('EXCHANGE_RATE', $entry);
        }

        $transaction_history = $this->bank_helper->get_transaction_history($user_id);
        foreach($transaction_history as $entry)
        {
            $type = $entry['type'];
            $data = unserialize($entry['data']);
            $comment = $data['comment'];
            $entry['comment'] = $comment;
            $this->template->assign_block_vars('HISTORY', $entry);
        }

        $uinv = $this->user_inventory_helper;
        $inv_count = $uinv->get_inventory_count_by_product_class($user_id);

        $mk = $this->market_helper;
        $product_classes = $mk->get_product_classes();
        foreach($product_classes as $entry)
        {
            $id = $entry['id'];
            $entry['n_bought'] = array_key_exists($id, $inv_count) ? $inv_count[$id] : 0;
            $entry['b_buy'] = $entry['n_bought'] < $entry['max_per_user'];
            $entry['max_purchasable'] = $entry['max_per_user'] - $entry['n_bought'];
            $entry['price_formatted'] = number_format($entry['price']);
            $entry['json'] = json_encode($entry);
            $this->template->assign_block_vars('PRODUCT_CLASSES', $entry);
        }

        $invoice_id = $mk->create_invoice($user_id);
        $data = [
            'product_class_id' => 1,
            'price' => 5000,
            'quantity' => 1,
        ];
        $mk->append_to_invoice($invoice_id, $data);


        $elapsed_time = microtime(true) - $time;
        $this->template->assign_vars([
            'ELAPSED_TIME' => $elapsed_time,
            'N_TOKEN' => number_format($n_token),
            'N_INV_PTS' => $n_avail_inv_pts,
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp test - Statistics');
    }/*}}}*/

    private function get_token($user_id)
    {
        $sql = 'SELECT snp_bank_n_token FROM ' . USERS_TABLE . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row)
        {
            return (int) $row['snp_bank_n_token'];
        }
        return 0;
    }/*}}}*/

    private function get_available_invite_points($user_id)
    {
        $ih = new invite_helper(
            $this->container, $this->user, $this->auth, null,
            $this->db, $this->config, null, null);
        $invite_user_data = $ih->select_invite_user($user_id);
        if (!$invite_user_data)
        {
            return 0;
        }
        return $invite_user_data['n_available'];
    }/*}}}*/

}
