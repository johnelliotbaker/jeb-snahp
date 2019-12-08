<?php
namespace jeb\snahp\controller\economy;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;
use jeb\snahp\core\invite_helper;

class economy_user_dashboard
{
    protected $db;
    protected $user;
    protected $auth;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $sauth;
    protected $bank_user_account;
    protected $market;
    protected $user_inventory;
    protected $product_class;
    protected $exchange_rates;
    protected $bank_transaction_logger;
    protected $market_transaction_logger;

    public function __construct(
        $db, $user, $auth, $config, $request, $template, $container, $helper,
        $tbl,
        $sauth, $bank_user_account, $market, $user_inventory, $product_class,
        $exchange_rates, $bank_transaction_logger, $market_transaction_logger
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->sauth = $sauth;
        $this->tbl = $tbl;
        $this->bank_user_account = $bank_user_account;
        $this->market = $market;
        $this->user_inventory = $user_inventory;
        $this->product_class = $product_class;
        $this->exchange_rates = $exchange_rates;
        $this->bank_transaction_logger = $bank_transaction_logger;
        $this->market_transaction_logger = $market_transaction_logger;
        $this->user_id = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon();
        // $allowed_groupset = 'Red Team';
        // $sauth->reject_user_not_in_groupset($this->user_id, $allowed_groupset);
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        switch ($mode)
        {
        case 'user_manager':
            $cfg['tpl_name'] = '@jeb_snahp/economy/mcp/user_manager/base.html';
            return $this->handle_user_manager($cfg);
        case 'exchange':
            $cfg = [];
            return $this->handle_exchange($cfg);
        case 'buy_product':
            $cfg = [];
            return $this->handle_buy_product($cfg);
        case 'reset_user':
            return $this->reset_user();
        case 'set_user_balance':
            return $this->set_user_balance();
        case 'overview':
            $cfg['tpl_name'] = '@jeb_snahp/economy/ucp/user_dashboard/base.html';
            return $this->handle_overview($cfg);
        case 'process_exchange_confirm':
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    public function handle_overview($cfg)/*{{{*/
    {
        $time = microtime(true);
        $user_id = $this->user_id;
        $balance = $this->bank_user_account->get_balance($user_id);
        $n_avail_inv_pts = $this->get_available_invite_points($user_id);
        $exchange_rates = $this->exchange_rates->get_exchange_rates();
        foreach($exchange_rates as $entry)
        {
            $entry['sell_rate_formatted'] = number_format($entry['sell_rate']);
            $entry['json'] = json_encode($entry);
            $this->template->assign_block_vars('EXCHANGE_RATE', $entry);
        }
        [$rowset, $total] = $this->market_transaction_logger->get_user_market_transactions($user_id);
        $rowset = array_slice($rowset, 0, 30);
        foreach($rowset as $entry)
        {
            $entry['created_time_strn'] = $this->user->format_date($entry['created_time']);
            $data = unserialize($entry['data']);
            $comment = isset($data['comment']) ? $data['comment'] : '';
            $entry['comment'] = $comment;
            $this->template->assign_block_vars('INVOICES', $entry);
        }
        [$rowset, $total] = $this->bank_transaction_logger->get_user_bank_transactions($user_id);
        $rowset = array_slice($rowset, 0, 30);
        foreach($rowset as $entry)
        {
            $type = $entry['type'];
            $entry['created_time_strn'] = $this->user->format_date($entry['created_time']);
            $data = unserialize($entry['data']);
            $comment = isset($data['comment']) ? $data['comment'] : '';
            $entry['comment'] = $comment;
            $this->template->assign_block_vars('HISTORY', $entry);
        }
        $inv_count = $this->user_inventory->get_inventory_count_by_product_class($user_id);
        $product_classes = $this->product_class->get_product_classes();
        $group_data = $this->get_group_data();
        foreach($product_classes as $entry)
        {
            if ($entry['name'] == 'search_cooldown_reducer')
            {
                $entry['max_per_user'] = min($entry['max_per_user'], $this->get_max_search_interval());
            }
            $id = $entry['id'];
            $entry['n_bought'] = array_key_exists($id, $inv_count) ? $inv_count[$id] : 0;
            $entry['b_buy'] = $entry['n_bought'] < $entry['max_per_user'];
            $entry['max_purchasable'] = $entry['max_per_user'] - $entry['n_bought'];
            $entry['price_formatted'] = number_format($entry['price']);
            $entry['json'] = json_encode($entry);
            $this->template->assign_block_vars('PRODUCT_CLASSES', $entry);
        }
        $elapsed_time = microtime(true) - $time;
        $this->template->assign_vars([
            'ELAPSED_TIME' => $elapsed_time,
            'BALANCE' => number_format($balance),
            'N_INV_PTS' => $n_avail_inv_pts,
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp test - Statistics');
    }/*}}}*/

    private function set_user_balance()/*{{{*/
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

    private function reset_user()/*{{{*/
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

    private function has_enough_balance($total_cost, $user_id)/*{{{*/
    {
        $balance = (int) $this->bank_user_account->get_balance($user_id);
        if ((int) $total_cost > $balance)
        {
            return false;
        }
        return true;
    }/*}}}*/

    private function get_total_cost($quantity, $product_class_id)/*{{{*/
    {
        return (int) $this->market->get_total_cost($product_class_id, $quantity);
    }/*}}}*/

    private function can_buy($required_quantity, $product_class_id, $user_id)/*{{{*/
    {
        $product_class_id = (int) $product_class_id;
        // Check if requested quantity doesn't violate max_per_user
        $prod_data = $this->product_class->get_product_class($product_class_id);
        if (!$prod_data) return [0, 'That item does not exist.'];
        $max_per_user = $prod_data['max_per_user'];
        $inv_data = $this->user_inventory->get_single_inventory("product_class_id=${product_class_id}" , $user_id);
        $existing_quantity = $inv_data ? $inv_data['quantity'] : 0;
        $purchasable_quantity = $max_per_user - $existing_quantity;
        if ($required_quantity > $purchasable_quantity) return [0, "You cannot purchase more than ${purchasable_quantity}."];
        // Check if there is enough fund
        $total_cost = $this->get_total_cost($required_quantity, $product_class_id);
        if (!$this->has_enough_balance($total_cost, $user_id)) return [0, 'Insufficient fund.'];
        return [1, 'Success'];
    }/*}}}*/

    public function handle_buy_product($cfg)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $product_class_id = $this->request->variable('pcid', 0);
        $quantity = $this->request->variable('quantity', 0);
        $user_id = $this->request->variable('user_id', 0);
        $user_id = $user_id > 0 ? $user_id : $this->user_id;
        // Check user has fund to complete purchase
        $total_cost = $this->get_total_cost($quantity, $product_class_id);
        [$b_purchasable, $reason] = $this->can_buy($quantity, $product_class_id, $user_id);
        if (!$b_purchasable)
        {
            return $js->send(['status'=> 0, 'reason' => $reason]);
        }
        // Withdraw balance
        $b_bank = $this->bank_user_account->withdraw($total_cost, $user_id);
        if (!$b_bank)
        {
            return $js->send(['status'=> 0, 'reason' => 'Could not process banking transaction.']);
        }
        else
        {
            $this->bank_user_account->log_withdraw($total_cost, $user_id, $broker_id=-1);
        }
        // Add purchased item to user inventory
        $b_inv = $this->user_inventory->do_add_item_with_logging($product_class_id, $quantity, $user_id);
        if (!$b_inv)
        {
            return $js->send(['status' => 0, 'reason'=> 'Could not add inventory to the user.']);
        }
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }/*}}}*/

    public function handle_exchange($cfg)/*{{{*/
    {
        $user_id = $this->user_id;
        $amount = $this->request->variable('amount', 0);
        $exchange_rate_id = $this->request->variable('id', 0);
        $direction = $this->request->variable('dir', 'sell');
        [$b_success, $reason] = $this->bank_user_account->do_exchange($exchange_rate_id, $direction, $amount, $user_id);
        $data = ['status' => $b_success, 'reason' => $reason];
        $js = new \phpbb\json_response();
        return $js->send($data);
    }/*}}}*/

    public function handle_user_manager($cfg)/*{{{*/
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

    private function get_available_invite_points($user_id)/*{{{*/
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

    private function get_group_data()/*{{{*/
    {
        $group_id = $this->user->data['group_id'];
        $sql = 'SELECT * FROM ' . GROUPS_TABLE . " WHERE group_id=${group_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    private function get_max_search_interval()
    {
        $group_data = $this->get_group_data();
        $base_search_interval = (int) $group_data['snp_search_interval'];
        return max($base_search_interval-5, 0);

    }
}
