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

    public function handle_working()
    {
        $this->reject_non_moderator();
        $requestdata = $this->select_request_closed();
        $a_tid = [];
        foreach ($requestdata as $req)
            $a_tid[] = $req['tid'];
        if (!$a_tid) return false;
        $td = $this->get_topic_data($a_tid);
        $this->topic_mover->move_topics($td, 24);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['req'] .
            ' SET b_graveyard = 1 ' .
            ' WHERE ' . $this->db->sql_in_set('tid', $a_tid);
        $this->db->sql_query($sql);
        trigger_error('Items have been moved to graveyard.');
    }

    public function handle()
    {
        $this->reject_non_moderator();
        $count = 0;
        $to = 24;
        while ($count < 100000)
        {
            if ($to == 24)
            {
                $to = 21;
            }
            // $requestdata = $this->select_request_closed();
            // $a_tid = [];
            // foreach ($requestdata as $req)
            //     $a_tid[] = $req['tid'];
            $a_tid = [1434];
            if (!$a_tid) return false;
            $td = $this->get_topic_data($a_tid);
            $this->topic_mover->move_topics($td, $to);
            $tbl = $this->container->getParameter('jeb.snahp.tables');
            $sql = 'UPDATE ' . $tbl['req'] .
                ' SET b_graveyard = 1 ' .
                ' WHERE ' . $this->db->sql_in_set('tid', $a_tid);
            $this->db->sql_query($sql);
            $count += 1;
        }
        trigger_error('Items have been moved to graveyard.');
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
