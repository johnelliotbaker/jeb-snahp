<?php
namespace jeb\snahp\core\bank;

class bank_transaction_logger
{
    protected $db;
    protected $tbl;
    public function __construct(
        $db,
        $tbl
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
    }

    public function create_single_transaction($user_id, $broker_id, $data)
    {
        $transaction_id = $this->create_transaction($user_id, $broker_id);
        return $this->append_to_transaction($transaction_id, $data);
    }

    public function append_to_transaction($transaction_id, $data)
    {
        $data['transaction_id'] = $transaction_id;
        $sql = 'INSERT INTO ' . $this->tbl['bank_transaction_items'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }

    public function create_transaction($user_id, $broker_id=-1)
    {
        $user_id = (int) $user_id;
        $broker_id = (int) $broker_id;
        if ($user_id < 1) {
            return false;
        }
        $data = [
            'created_time' => time(),
            'user_id' => $user_id,
            'broker_id' => $broker_id,
        ];
        $sql = 'INSERT INTO ' . $this->tbl['bank_transactions'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return (int) $this->db->sql_nextid();
    }


    public function get_user_bank_transactions($user_id)
    {
        $maxi_query = 300;
        $cooldown = 0;
        $where = "user_id=${user_id}";
        $order_by = 'a.id DESC';
        $sql_array = [
            'SELECT'    => '*',
            'FROM'      => [ $this->tbl['bank_transactions'] => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM' => [$this->tbl['bank_transaction_items'] => 'b'],
                    'ON'   => 'a.id=b.transaction_id',
                ],
            ],
            'WHERE'    => $where,
            'ORDER_BY' => $order_by,
        ];
        $per_page = 50;
        $start = 0;
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $maxi_query, 0, $cooldown);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start, $cooldown);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }

    public function logger()
    {
    }
}
