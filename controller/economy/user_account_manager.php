<?php
namespace jeb\snahp\controller\economy;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Economy :: MCP :: Administer user account information
 * */

class user_account_manager
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $sauth;
    protected $bank_user_account;
    protected $user_inventory;
    protected $product_class;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $sauth,
        $bank_user_account,
        $user_inventory,
        $product_class
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->sauth = $sauth;
        $this->bank_user_account = $bank_user_account;
        $this->user_inventory = $user_inventory;
        $this->product_class = $product_class;
        $this->user_id = (int) $this->user->data['user_id'];
        $sauth->reject_non_dev('Error Code: 8b16954df1');
    }

    public function handle($mode)/*{{{*/
    {
        switch ($mode) {
        case 'user_manager':
            $cfg['tpl_name'] = '@jeb_snahp/economy/mcp/user_manager/base.html';
            return $this->handle_user_manager($cfg);
        case 'reset_user':
            return $this->reset_user();
        case 'get_user_account':
            return $this->respond_with_user_account_as_json();
        case 'get_user_inventory':
            return $this->get_user_inventory_json();
        case 'get_user_balance':
            return $this->get_user_balance_json();
        case 'set_user_balance':
            return $this->handle_set_user_balance();
        case 'set_user_inventory_item':
            return $this->handle_set_user_inventory_item();
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }

    public function handle_user_manager($cfg)/*{{{*/
    {
        return $this->helper->render($cfg['tpl_name'], 'Snahp Economy User Account Manager');
    }

    private function handle_set_user_balance()/*{{{*/
    {
        $js = new \phpbb\json_response();
        $user_id = $this->get_requested_user_id();
        $balance = $this->get_requested_balance();
        $broker_id = $this->user_id;
        $b_success = $this->set_user_balance($user_id, $balance, $broker_id);
        if ($b_success) {
            $this->bank_user_account->log_moderation($balance, $user_id, $broker_id);
        }
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }

    private function respond_with_user_account_as_json()/*{{{*/
    {
        $user_id = $this->request->variable('u', 0);
        if (!$user_id) {
            return false;
        }
        $js = new \phpbb\json_response();
        $inventory = $this->get_user_inventory($user_id);
        $res['balance'] = $this->get_user_balance($user_id);
        $res['inventory'] = $this->get_user_inventory($user_id);
        return $js->send($res);
    }

    private function get_user_inventory($user_id)/*{{{*/
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return false;
        }
        $pclasses = $this->product_class->get_product_classes();
        $inventory = $this->user_inventory->get_inventory($user_id);
        $tmp = [];
        foreach ($inventory as $inv) {
            $tmp[$inv['product_class_id']] = $inv;
        }
        $res = [];
        foreach ($pclasses as $pc) {
            if (array_key_exists($pc['id'], $tmp)) {
                $pc['quantity'] = $tmp[$pc['id']]['quantity'];
            } else {
                $pc['quantity'] = 0;
            }
            $res[] = $pc;
        }
        return $res;
    }

    private function get_user_inventory_json($user_id)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $inventory = $this->get_user_inventory($user_id);
        return $js->send(['inventory'=> $res]);
    }

    private function get_user_balance($user_id)/*{{{*/
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return false;
        }
        return $this->bank_user_account->get_balance($user_id);
    }

    private function get_user_balance_json($user_id)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $balance = $this->get_user_balance($user_id);
        return $js->send(['balance'=> $balance]);
    }

    private function get_requested_user_id()/*{{{*/
    {
        $user_id = $this->request->variable('u', 0);
        if ($user_id < 1) {
            return false;
        }
        return $user_id;
    }

    private function get_requested_balance()/*{{{*/
    {
        $balance = $this->request->variable('b', -1);
        if ($balance < 0) {
            return false;
        }
        return $balance;
    }

    private function get_requested_product_class_id()/*{{{*/
    {
        $product_class_id = $this->request->variable('pcid', 0);
        if (!$product_class_id) {
            return false;
        }
        return $product_class_id;
    }

    private function get_requested_quantity()/*{{{*/
    {
        $quantity = $this->request->variable('quantity', -1);
        if ($quantity < 0) {
            return false;
        }
        return $quantity;
    }

    private function set_user_balance($user_id, $balance, $broker_id=-1)/*{{{*/
    {
        return $this->bank_user_account->set_balance($user_id, $balance, $broker_id);
    }

    private function handle_set_user_inventory_item()/*{{{*/
    {
        $user_id = $this->get_requested_user_id();
        $quantity = $this->get_requested_quantity();
        $product_class_id = $this->get_requested_product_class_id();
        if ($user_id===false || $quantity===false || $product_class_id===false) {
            return false;
        }
        $js = new \phpbb\json_response();
        $broker_id = $this->user_id;
        $b_success = $this->user_inventory->set_item_quantity($product_class_id, $quantity, $user_id, $broker_id);
        if ($b_success) {
            $comment = "Moderation: Set inventory item quantity to ${quantity}";
            $this->user_inventory->log_moderation($product_class_id, $quantity, $user_id, $broker_id, $comment);
        }
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }

    private function set_user_inventory_item()/*{{{*/
    {
        $user_id = $this->request->variable('u', 0);
        if (!$user_id) {
            return false;
        }
        $quantity = $this->request->variable('quantity', -1);
        if ($quantity < 0) {
            return false;
        }
        $product_class_id = $this->request->variable('pcid', 0);
        if (!$product_class_id) {
            return false;
        }
        $js = new \phpbb\json_response();
        $broker_id = $this->user_id;
        $this->user_inventory->set_item_quantity($product_class_id, $quantity, $user_id, $broker_id);
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }

    private function reset_user()/*{{{*/
    {
        $user_id = $this->request->variable('u', 0);
        if (!$user_id) {
            return false;
        }
        $js = new \phpbb\json_response();
        $this->bank_user_account->reset($user_id);
        return $js->send(['status' => 1, 'reason'=> 'Success']);
    }
}
