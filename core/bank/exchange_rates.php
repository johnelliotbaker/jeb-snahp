<?php
namespace jeb\snahp\core\bank;

class exchange_rates
{
    protected $db;
    protected $user;
    protected $sauth;
    protected $tbl;
	public function __construct(
        $db, $user,
        $tbl,
        $sauth
	)
	{
        $this->db = $db;
        $this->user = $user;
        $this->sauth = $sauth;
        $this->tbl = $tbl;
	}

    public function get_exchange_rate($id)/*{{{*/
    {
        $id = (int) $id;
        return $this->get_exchange_rates("id=${id}", $b_firstrow=true);
    }/*}}}*/

    public function get_exchange_rates($where='1=1', $b_firstrow=false)/*{{{*/
    {
        $sql = 'SELECT * FROM ' . $this->tbl['bank_exchange_rates'] . " WHERE ${where}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        if ($b_firstrow && count($rowset)>0)
        {
            return $rowset[0];
        }
        return $rowset;
    }/*}}}*/

}
