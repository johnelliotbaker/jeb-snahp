<?php
namespace jeb\snahp\core\user;

class user_inventory
{
    protected $db;
    protected $user;
    protected $container;
    protected $sauth;
    protected $market_transaction_logger;
    protected $product_class;
	public function __construct(
        $db, $user, $container,
        $tbl, 
        $sauth, $market_transaction_logger, $product_class
	)
	{/*{{{*/
        $this->db = $db;
        $this->user = $user;
        $this->container = $container;
        $this->sauth = $sauth;
        $this->tbl = $tbl;
        $this->market_transaction_logger = $market_transaction_logger;
        $this->product_class = $product_class;
        $this->user_id = $this->user->data['user_id'];
	}/*}}}*/

    public function reset($user_id, $broker_id=-1)/*{{{*/
    {
        $this->sauth->reject_non_dev('cec49a5984');
        $user_id = (int) $user_id;
        $this->set_balance($user_id, 0);
    }/*}}}*/

    public function get_single_inventory($where, $user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $sql = 'SELECT * FROM ' . $this->tbl['user_inventory'] . " WHERE user_id=${user_id} AND $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function get_inventory($user_id)/*{{{*/
    {
        $user_id = (int) $user_id;
        $sql = 'SELECT * FROM ' . $this->tbl['user_inventory'] . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    public function get_inventory_with_details($user_id)/*{{{*/
    {
        $where = "user_id=${user_id}";
        $order_by = 'i.id DESC';
        $sql_array = [
            'SELECT'	=> '*',
            'FROM'		=> [ $this->tbl['user_inventory'] => 'i', ],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [$this->tbl['mrkt_product_classes'] => 'p'],
                    'ON'	=> 'i.product_class_id=p.id',
                ],
            ],
            'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    public function get_inventory_by_product_class($product_class_id, $user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $product_class_id = (int) $product_class_id;
        $sql = 'SELECT * FROM ' . $this->tbl['user_inventory'] . " WHERE user_id=${user_id} AND product_class_id=${product_class_id}";
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
            $sql = 'UPDATE ' . $this->tbl['user_inventory'] . " SET quantity=quantity+${quantity}" . 
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
            $sql = 'INSERT INTO ' . $this->tbl['user_inventory'] . $this->db->sql_build_array('INSERT', $data);
            $this->db->sql_query($sql);
            return $this->db->sql_affectedrows() > 0;
        }
        return false;
    }/*}}}*/

    public function do_add_item_with_logging($product_class_id, $quantity, $user_id=null, $comment='')/*{{{*/
    {
        $b_item_added = $this->do_add_item($product_class_id, $quantity, $user_id);
        $product_class = $this->product_class->get_product_class($product_class_id);
        $price = $product_class['price'];
        $name = $product_class['display_name'];
        $price_formatted = number_format($product_class['price'] * $quantity);
        if (!$comment)
        {
            $comment = "Purchasing ${quantity} ${name} for $${price_formatted}";
        }
        $data = [
            'product_class_id' => $product_class_id,
            'price' => $price,
            'quantity' => $quantity,
            'data' => serialize(['comment' => $comment]),
        ];
        $this->market_transaction_logger->create_single_item_invoice($user_id, $broker_id=-1, $data);
        return $b_item_added;
    }/*}}}*/

    public function do_add_item($product_class_id, $quantity, $user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $this->user_id : (int) $user_id;
        $product_class_id = (int) $product_class_id;
        $quantity = abs((int) $quantity);
        $b_item_added = $this->add_item($product_class_id, $quantity, $user_id);
        return $b_item_added;
    }/*}}}*/

    public function set_item_quantity_public($product_class_id, $quantity, $user_id, $broker_id=-1)/*{{{*/
    {
        $b_success = false;
        if ($quantity < 1)
        {
            $b_success = $this->delete_item($product_class_id, $user_id, $broker_id);
        }
        else
        {
            $b_success = $this->upsert_inventory($product_class_id, $quantity, $user_id, $broker_id);
        }
        if ($b_success)
        {
            return true;
        }
        return false;
    }/*}}}*/

    public function set_item_quantity($product_class_id, $quantity, $user_id, $broker_id=-1)/*{{{*/
    {
        $this->sauth->reject_non_dev('Error Code: b48e5f2a69');
        $b_success = false;
        if ($quantity < 1)
        {
            $b_success = $this->delete_item($product_class_id, $user_id, $broker_id);
        }
        else
        {
            $b_success = $this->upsert_inventory($product_class_id, $quantity, $user_id, $broker_id);
        }
        if ($b_success)
        {
            return true;
        }
        return false;
    }/*}}}*/

    private function delete_item($product_class_id, $user_id, $broker_id=-1)/*{{{*/
    {
        $product_class_id = (int) $product_class_id;
        $user_id = (int) $user_id;
        $where = " WHERE user_id=${user_id} AND product_class_id=${product_class_id}";
        $sql = 'DELETE FROM ' . $this->tbl['user_inventory'] . $where;
        $this->db->sql_query($sql);
        $data = [
            'product_class_id' => $product_class_id,
            'price' => -1,
            'quantity' => 0,
        ];
        $b_logged = $this->log($user_id, $broker_id, $data);
        return $b_logged;
    }/*}}}*/

    private function upsert_inventory($product_class_id, $quantity, $user_id, $broker_id=-1)/*{{{*/
    {
        $data = [
            'user_id' => $user_id,
            'product_class_id' => $product_class_id,
            'quantity' => $quantity
        ];
        $sql = 'INSERT INTO ' . $this->tbl['user_inventory'] . $this->db->sql_build_array('INSERT', $data) . "
            ON DUPLICATE KEY UPDATE quantity=${quantity}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    private function log_add_item($user_id, $broker_id, $data)/*{{{*/
    {
        return $this->log($user_id, $broker_id, $data);
    }/*}}}*/

    public function log_moderation($product_class_id, $quantity, $user_id, $broker_id, $comment='')/*{{{*/
    {
        $data = [
            'product_class_id' => $product_class_id,
            'price' => -1,
            'quantity' => $quantity,
            'data' => serialize(['comment'=> $comment]),
        ];
        $b_logged = $this->log($user_id, $broker_id, $data);
    }/*}}}*/

    private function log($user_id, $broker_id, $data)/*{{{*/
    {
        if ($this->db->sql_affectedrows() < 1)
        {
            return false;
        }
        if ($data===null)
        {
            return false;
        }
        return $this->market_transaction_logger->create_single_item_invoice($user_id, $broker_id, $data);
    }/*}}}*/

}
