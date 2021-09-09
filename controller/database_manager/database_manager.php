<?php
namespace jeb\snahp\controller\database_manager;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum database_manager
 * */

class database_manager
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
    }

    public function handle_commands()
    {
        if (!$this->sauth->is_only_dev()) {
            trigger_error('You do not have the permission to view this page. Error Code: 689a2f9db0');
        }
        $command = $this->request->variable('command', '');
        switch ($command) {
        case 'start_logging':
            $this->start_logging();
            break;
        case 'clear_log':
            $this->clear_log();
            break;
        case 'stop_logging':
            $this->stop_logging();
            break;
        default:
        }
        $cfg['tpl_name'] = '@jeb_snahp/database_manager/base.html';
        return $this->respond_query($cfg);
    }


    private function clear_log()
    {
        $sql = 'Truncate mysql.general_log;';
        $this->db->sql_query($sql);
    }

    private function start_logging()
    {
        $sql = 'SET GLOBAL log_output="TABLE";';
        $this->db->sql_query($sql);
        $sql = 'SET GLOBAL general_log = "ON";';
        $this->db->sql_query($sql);
    }

    private function stop_logging()
    {
        $sql = 'SET GLOBAL log_output="TABLE";';
        $this->db->sql_query($sql);
        $sql = 'SET GLOBAL general_log = "OFF";';
        $this->db->sql_query($sql);
    }

    private function set_logging_status()
    {
        $sql = 'Show variables like "general_log%"';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row['Value']=='ON') {
            $this->template->assign_vars([
                'B_LOGGING' => true,
            ]);
        } else {
            $this->template->assign_vars([
                'B_LOGGING' => false,
            ]);
        }
    }

    public function handle()
    {
        if (!$this->sauth->is_only_dev()) {
            trigger_error('You do not have the permission to view this page. Error Code: 88822a5b09');
        }
        $cfg['tpl_name'] = '@jeb_snahp/database_manager/base.html';
        return $this->respond_query($cfg);
    }

    private function exec_sql($sql)
    {
        // $sql = $this->db->sql_escape($sql);
        $result = $this->db->sql_query($sql);
        if (!is_bool($result)) {
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
        } else {
            $rowset = [];
        }
        return $rowset;
    }

    private function respond_query($cfg)
    {
        $this->set_logging_status();
        add_form_key('jeb_snp');
        $format = $this->request->variable('format', '');
        $sql = htmlspecialchars_decode($this->request->variable('database_manager_statement', ''));
        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('jeb_snp')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            if ($sql) {
                $timeit0 = microtime(true);
                $format = $this->request->variable('as_csv', 'off') === 'on' ? 'csv' : '';
                $rowset = $this->exec_sql($sql);
                if ($format === 'csv') {
                    foreach ($rowset as $row) {
                        $tmp = implode(', ', $row);
                        $tmp = preg_replace('#[\n\r\s]+#', ' ', $tmp);
                        $data[] = preg_replace('#[\n\r]#', ' ', $tmp);
                    }
                    $rowset = 'Empty rowset.';
                    if (isset($data)) {
                        $rowset = implode('<br>', $data);
                    }
                }
                $process_time = microtime(true) - $timeit0;
                $this->template->assign_vars([
                    'ROWSET' => $rowset,
                    'PROCESS_TIME' => $process_time,
                ]);
            }
        }
        $this->template->assign_vars([
            'DATABASE_MANAGER_STATEMENT' => $sql,
            'S_FORMAT' => $format,
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Database Manager');
    }/*}}*/
}
