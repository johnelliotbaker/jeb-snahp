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
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
    }/*}}}*/

    public function handle()/*{{{*/
    {
        if (!$this->sauth->is_only_dev())
        {
            trigger_error('You do not have the permission to view this page. Error Code: 88822a5b09');
        }
        $cfg['tpl_name'] = '@jeb_snahp/database_manager/base.html';
        return $this->respond_query($cfg);
    }/*}}}*/

    private function exec_sql($sql)
    {
        // $sql = $this->db->sql_escape($sql);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    private function respond_query($cfg)/*{{{*/
    {
        add_form_key('jeb_snp');
        if ($this->request->is_set_post('submit'))
        {
            if (!check_form_key('jeb_snp'))
            {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            $sql = htmlspecialchars_decode($this->request->variable('database_manager_statement', ''));
            if ($sql)
            {
                $rowset = $this->exec_sql($sql);
                $this->template->assign_vars([
                    'DATABASE_MANAGER_STATEMENT' => $sql,
                    'ROWSET' => $rowset,
                ]);
            }
        }
        return $this->helper->render($cfg['tpl_name'], 'Database Manager');
    }/*}}*/


}
