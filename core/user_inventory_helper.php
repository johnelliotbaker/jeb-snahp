<?php

namespace jeb\snahp\core;

class user_inventory_helper
{

/*{{{*/
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
        $tbl) /*{{{*/
    {
        $this->config = $config;
        $this->db = $db;
        $this->auth = $auth;
        $this->template = $template;
        $this->user = $user;
        $this->cache = $cache;
        $this->container = $phpbb_container;
        $this->tbl_inventory = $tbl['user_inventory'];
        $this->user_id = (int) $user->data['user_id'];
    }/*}}}*/

    public function get_single_inventory($where, $user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $sql = 'SELECT * FROM ' . $this->tbl_inventory . " WHERE user_id=${user_id} AND $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function get_inventory($user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $sql = 'SELECT * FROM ' . $this->tbl_inventory . " WHERE user_id=${user_id} AND enable=1";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    public function get_inventory_by_product_class($product_class_id, $user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $product_class_id = (int) $product_class_id;
        $sql = 'SELECT * FROM ' . $this->tbl_inventory . " WHERE user_id=${user_id} AND product_class_id=${product_class_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function get_inventory_count_by_product_class($user_id)/*{{{*/
    {
        $rowset = $this->get_inventory((int)$user_id);
        if (!$rowset) return [];
        $res = [];
        foreach ($rowset as $row)
        {
            $res[$row['product_class_id']] = (int) $row['quantity'];
        }
        return $res;
    }/*}}}*/

    public function add_item($product_class_id, $quantity, $user_id=null)/*{{{*/
    {
        $row = $this->get_inventory_by_product_class($product_class_id, $user_id);
        if ($row)
        {
            $sql = 'UPDATE ' . $this->tbl_inventory . " SET quantity=quantity+${quantity}" . 
                " WHERE user_id=${user_id} AND product_class_id=${product_class_id}";
            $this->db->sql_query($sql);
            return $this->db->sql_affectedrows() > 0;
        }
        else
        {
            $data = [
                'user_id' => $user_id,
                'product_class_id' => $product_class_id,
                'quantity' => $quantity,
                'enable' => 1,
            ];
            $sql = 'INSERT INTO ' . $this->tbl_inventory . $this->db->sql_build_array('INSERT', $data);
            $this->db->sql_query($sql);
            return $this->db->sql_affectedrows() > 0;
        }
        return false;
    }/*}}}*/

    public function do_add_item($product_class_id, $quantity, $user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $product_class_id = (int) $product_class_id;
        $quantity = abs((int) $quantity);
        $b_item_added = $this->add_item($product_class_id, $quantity, $user_id);
        return $b_item_added;
    }/*}}}*/

    private function insert_or_update_inventory($product_class_id, $quantity, $user_id)/*{{{*/
    {
    }/*}}}*/

}
