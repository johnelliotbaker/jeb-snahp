<?php
namespace jeb\snahp\core\cron;

class helper
{
    protected $db;
    protected $user;
    protected $auth;
    protected $config;
    protected $container;
    protected $tbl;
    protected $sauth;
    protected $user_id;
    public function __construct(
        $db, $user, $auth, $config, $container,
        $tbl,
        $sauth
    )
    {/*{{{*/
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = (int) $this->user->data['user_id'];
    }/*}}}*/

    public function add_log_item($data)/*{{{*/
    {
        $sql = 'INSERT INTO ' . $this->tbl['cron_log'] . ' ' . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    public function log($type_name='feedpostbot', $item_name='hourly', $status='log')
    {
        $data = [
            'user_id' => $this->user_id,
            'created_time' => time(),
            'type_name' => $type_name,
            'item_name' => $item_name,
            'status' => $status,
            'data' => serialize([]),
        ];
        $this->add_log_item($data);
    }

}
