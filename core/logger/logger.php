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
        $this->sql_limit = 500;
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

    public function get_user_oldest_visit_time($user_id)/*{{{*/
    {
        $udata = $this->user->data;
        $timestrn = $udata['snp_log_visit_timestamps'];
        $a_time = explode(',', $timestrn);
        return (int) $a_time[0];
    }/*}}}*/

    public function reset_user_visit_time($user_id)/*{{{*/
    {
        $n_buffer = (int) $this->config['snp_log_user_spam_buffer_length']; // In seconds
        $a_time = array_fill(0, $n_buffer, 0);
        $timestrn = implode(',', $a_time);
        prn($timestrn);
        $data = ['snp_log_visit_timestamps' => $timestrn];
        $sql = 'UPDATE ' . USERS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function validate_or_reset_user_visit_time($user_id)/*{{{*/
    {
        $n_buffer = (int) $this->config['snp_log_user_spam_buffer_length'];
        $udata = $this->user->data;
        $timestrn = $udata['snp_log_visit_timestamps'];
        $a_time = explode(',', $timestrn);
        if (count($a_time) != $n_buffer || !is_numeric($a_time[0]))
        {
            $this->reset_user_visit_time($user_id);
            return false;
        }
        return true;
    }/*}}}*/

    public function log_user_visit($user_id)/*{{{*/
    {
        $udata = $this->user->data;
        $timestrn = $udata['snp_log_visit_timestamps'];
        $a_time = explode(',', $timestrn);
        $a_time = array_slice($a_time, 1);
        $a_time[] = time();
        $timestrn = implode(',', $a_time);
        $data = ['snp_log_visit_timestamps' => $timestrn];
        $sql = 'UPDATE ' . USERS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function log_foe_block($logger_id, $name='ADD_USER', $created_time=false, $extra=[])/*{{{*/
    {
        $blocked_id = isset($extra['blocked_id']) ? $extra['blocked_id'] : 0;
        $data = [
            'user_id' => (int) $logger_id,
            'target_id' => (int) $blocked_id,
            'type' => 'LOG_FOE_BLOCKER',
            'name' => $name,
            'created_time' => $created_time ? $created_time : time(),
            'data' => serialize($extra)
        ];
        return $this->insert_log($data);
    }/*}}}*/

    public function get_count($sql_array)/*{{{*/
    {
		$count_logs_sql_ary = $sql_array;
		$count_logs_sql_ary['SELECT'] = 'COUNT(id) AS total_entries';
		unset($count_logs_sql_ary['ORDER_BY']);
		$sql = $this->db->sql_build_query('SELECT', $count_logs_sql_ary);
		$result = $this->db->sql_query($sql);
		$count = (int) $this->db->sql_fetchfield('total_entries');
		$this->db->sql_freeresult($result);
        return $count;
    }/*}}}*/

    public function select_foe_block($start=0, $per_page=50, $order_by='id DESC', $whereExtra='')/*{{{*/
    {
        $cooldown = 1;
        $where = ['type="LOG_FOE_BLOCKER"'];
        if ($whereExtra) {
            $where[] = $this->db->sql_escape($whereExtra);
        }
        $where = implode(' AND ', $where);
        $sql_array = [
            'SELECT'	=> 'a.*,
            b.user_id as blocker_user_id, b.username as blocker_username, b.user_colour as blocker_user_colour,
            c.user_id as blocked_user_id, c.username as blocked_username, c.user_colour as blocked_user_colour',
            'FROM'		=> [$this->tbl['log']=> 'a'],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [USERS_TABLE => 'b'],
                    'ON'	=> 'a.user_id=b.user_id',
                ],
                [
                    'FROM'	=> [USERS_TABLE => 'c'],
                    'ON'	=> 'a.target_id=c.user_id',
                ],
            ],
            'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $total = $this->get_count($sql_array);
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $per_page, $start, $cooldown);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }/*}}}*/

}
