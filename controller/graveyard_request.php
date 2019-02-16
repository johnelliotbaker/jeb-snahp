<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;
use jeb\snahp\core\topic_mover;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class graveyard_request extends base
{
    protected $prefix;
    protected $topic_mover;

    public function __construct($prefix, topic_mover $topic_mover)
    {
        $this->prefix = $prefix;
        $this->topic_mover = $topic_mover;
    }

    public function select_request_closed($limit=100)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def_closed = $def['set']['closed'];
        $sql = 'SELECT * FROM ' . $tbl['req'] .
            ' WHERE ' . $this->db->sql_in_set('status', $def_closed) .
            ' AND b_graveyard = 0';
        $result = $this->db->sql_query_limit($sql, $limit);
        $data = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function handle()
    {
        $this->reject_non_moderator();
        $batch_limit = 50;
        $requestdata = $this->select_request_closed($batch_limit);
        $a_tid = [];
        foreach ($requestdata as $req)
            $a_tid[] = $req['tid'];
        if (!$a_tid)
        {
            trigger_error("There are no closed requests to move.");
        }
        $td = $this->get_topic_data($a_tid);
        $graveyard_fid = unserialize($this->config['snp_cron_graveyard_fid'])['default'];
        $this->topic_mover->move_topics($td, $graveyard_fid);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['req'] .
            ' SET b_graveyard = 1 ' .
            ' WHERE ' . $this->db->sql_in_set('tid', $a_tid);
        $this->db->sql_query($sql);
        $len = count($a_tid);
        trigger_error("$len items have been moved to graveyard.");
    }

    protected function get_topic_data(array $topic_ids)
    {
        if (!function_exists('phpbb_get_topic_data'))
        {
            include('includes/functions_mcp.php');
        }
        return phpbb_get_topic_data($topic_ids);
    }

}
