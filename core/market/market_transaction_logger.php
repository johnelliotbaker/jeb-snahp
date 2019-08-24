<?php
namespace jeb\snahp\core\market;

class market_transaction_logger
{
    protected $db;
    protected $tbl;
	public function __construct(
        $db,
        $tbl
	)
	{
        $this->db = $db;
        $this->tbl = $tbl;
	}

    public function create_single_item_invoice($user_id, $broker_id=-1, $data)/*{{{*/
    {
        $invoice_id = $this->create_invoice($user_id, $broker_id);
        return $this->append_to_invoice($invoice_id, $data);
    }/*}}}*/

    public function append_to_invoice($invoice_id, $data)/*{{{*/
    {
        $data['invoice_id'] = $invoice_id;
        $sql = 'INSERT INTO ' . $this->tbl['mrkt_invoice_items'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function create_invoice($user_id, $broker_id=-1)/*{{{*/
    {
        $user_id = (int) $user_id;
        $broker_id = (int) $broker_id;
        if ($user_id < 1) return false;
        $data = [
            'created_time' => time(),
            'user_id' => $user_id,
            'broker_id' => $broker_id,
        ];
        $sql = 'INSERT INTO ' . $this->tbl['mrkt_invoices'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return (int) $this->db->sql_nextid();
    }/*}}}*/

}
