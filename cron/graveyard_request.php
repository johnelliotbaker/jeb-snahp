<?php

namespace jeb\snahp\cron;

use jeb\snahp\core\topic_mover;
use phpbb\db\driver\driver_interface;

class graveyard_request extends \phpbb\cron\task\base
{
    protected $config;
    protected $topic_mover;
    protected $container;

    public function __construct(
        \phpbb\config\config $config,
        topic_mover $topic_mover,
        $db,
        $container
    ) {
        $this->config      = $config;
        $this->topic_mover = $topic_mover;
        $this->db          = $db;
        $this->container   = $container;
    }

    protected function get_topic_data(array $topic_ids)
    {
        if (!function_exists('phpbb_get_topic_data')) {
            include('includes/functions_mcp.php');
        }
        return phpbb_get_topic_data($topic_ids);
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
        $data = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $data[] = $row;
        }
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function exec_move()
    {
        $batch_limit = 100;
        $requestdata = $this->select_request_closed($batch_limit);
        $a_tid = [];
        foreach ($requestdata as $req) {
            $a_tid[] = $req['tid'];
        }
        if (!$a_tid) {
            return false;
        }
        $td = $this->get_topic_data($a_tid);
        $graveyard_fid = unserialize($this->config['snp_cron_graveyard_fid'])['default'];
        $this->topic_mover->move_topics($td, $graveyard_fid);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['req'] .
            ' SET b_graveyard = 1 ' .
            ' WHERE ' . $this->db->sql_in_set('tid', $a_tid);
        $this->db->sql_query($sql);
    }

    public function run()
    {
        $this->exec_move();
        $this->config->set('snp_cron_graveyard_last_gc', time());
    }

    public function is_runnable()
    {
        return (int) $this->config['snp_cron_b_graveyard'];
    }

    public function should_run()
    {
        return $this->config['snp_cron_graveyard_last_gc'] < time() - $this->config['snp_cron_graveyard_gc'];
    }
}
