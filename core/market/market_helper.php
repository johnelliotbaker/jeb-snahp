<?php

namespace jeb\snahp\core\market;
use jeb\snahp\core\invite_helper;


class market_helper
{


    protected $config;
    protected $db;
    protected $auth;
    protected $template;
    protected $user;
    protected $cache;


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
        $this->tbl_invoice_items = $tbl['mrkt_invoice_items'];
        $this->tbl_invoices = $tbl['mrkt_invoices'];
        $this->tbl_product_classes = $tbl['mrkt_product_classes'];
        $this->user_id = $user->data['user_id'];
    }

    public function get_product_classes()
    {
        $sql = 'SELECT * FROM ' . $this->tbl_product_classes . ' WHERE enable=1';
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function get_product_class_by_name($product_class_name)
    {
        $product_class_name = $this->db->sql_escape($product_class_name);
        $where = "name='${product_class_name}'";
        $sql = 'SELECT * FROM ' . $this->tbl_product_classes . " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function get_product_class($product_class_id)
    {
        $sql = 'SELECT * FROM ' . $this->tbl_product_classes . " WHERE id=${product_class_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function append_to_invoice($invoice_id, $data)
    {
        $data['invoice_id'] = $invoice_id;
        $sql = 'INSERT INTO ' . $this->tbl_invoice_items . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }

    public function create_invoice($user_id, $broker_id=-1)
    {
        $user_id = (int) $user_id;
        $broker_id = (int) $broker_id;
        if ($user_id < 1) return false;
        $data = [
            'created_time' => time(),
            'user_id' => $user_id,
            'broker_id' => $broker_id,
        ];
        $sql = 'INSERT INTO ' . $this->tbl_invoices . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return (int) $this->db->sql_nextid();
    }

    public function get_total_cost($product_class_id, $amount)
    {
        $product_class_id = (int) $product_class_id;
        $amount = (int) $amount;
        $product_class_data = $this->get_product_class($product_class_id);
        if (!$product_class_data)
        {
            return false;
        }
        $price = $product_class_data['price'];
        return abs($price * $amount);
    }

}
