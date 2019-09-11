<?php
namespace jeb\snahp\core\logger;

class logger
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $config_text;
    protected $tbl;
    protected $sauth;
    protected $sql_limit;
	public function __construct(/*{{{*/
        $db, $user, $config, $request, $template, $container, $helper, $config_text,
        $tbl,
        $sauth
	)
	{
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->config_text = $config_text;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->log_varname = 'devlog_posting';
        $this->sql_limit = 100;
        $this->user_id = $this->user->data['user_id'];
	}/*}}}*/

    private function is_type_allowed($data)/*{{{*/
    {
        if (!$data || !isset($data['type'])) { return false; }
        $type = $data['type'];
        return $this->config['snp_log_b_' . $type];
    }/*}}}*/

    private function insert_log($data)/*{{{*/
    {
        $sql = 'INSERT INTO ' . $this->tbl['log'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function log($data)/*{{{*/
    {
        if (!$this->is_type_allowed($data)) { return false; }
        $data['user_id'] = isset($data['user_id']) ? $data['user_id'] : $this->user_id;
        return $this->insert_log($data);
    }/*}}}*/

    public function get_log_by_type($type='posting')/*{{{*/
    {
        $where = "type='${type}'";
        return $this->select_log($where);
    }/*}}}*/

    private function select_log($where='1=1', $order_by='id DESC', $b_single=False)/*{{{*/
    {
        $sql = 'SELECT * FROM ' . $this->tbl['log'] . " WHERE ${where} ORDER BY ${order_by}";
        if ($b_single)
        {
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            return $row;
        }
        $result = $this->db->sql_query_limit($sql, $this->sql_limit);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

}
