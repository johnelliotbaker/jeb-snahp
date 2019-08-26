<?php
namespace jeb\snahp\controller\economy;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class product_class_editor
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $sauth;
    protected $product_class;
    protected $tbl;
    public function __construct(
        $db, $user, $config, $request, $template, $container, $helper,
        $tbl,
        $sauth, $product_class
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
        $this->product_class = $product_class;
        $this->user_id = (int) $this->user->data['user_id'];
        $sauth->reject_non_dev('f3a997395f');
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        switch ($mode)
        {
        case 'edit':
            $cfg['tpl_name'] = '@jeb_snahp/economy/mcp/product_class_editor/base.html';
            return $this->handle_edit($cfg);
        case 'save':
            return $this->handle_save();
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    private function handle_save()/*{{{*/
    {
        $json = (string) htmlspecialchars_decode($this->request->variable('json', ''));
        $data = json_decode($json);
        $tmp = [];
        foreach($data as $k=>$d)
        {
            $tmp[$k] = $d;
        }
        $data = $tmp;
        $b_success = $this->product_class->update_product_class($data['id'], $data);
        if ($b_success)
        {
            $rdata = [ 'status' => 'success', ];
        }
        else
        {
            $rdata = [ 'status' => 'error', ];
        }
        $js = new \phpbb\json_response();
        $js->send($rdata);
    }

    private function handle_edit($cfg)/*{{{*/
    {
        $rowset = $this->product_class->get_product_classes();
        foreach ($rowset as $row)
        {
            $row['json'] = json_encode($row);
            $this->template->assign_block_vars('PC', $row);
        }
        return $this->helper->render($cfg['tpl_name'], 'Product Class Editor');
    }

    private function get_bank_transaction_data_for_pagination($start, $per_page)/*{{{*/
    {
        $tbl_main = $this->tbl['bank_transactions'];
        $tbl_items = $this->tbl['bank_transaction_items'];
        $sql = 'SELECT COUNT(*) as total FROM ' . $tbl_main;
        $result = $this->db->sql_query($sql, 3600);
        $row = $this->db->sql_fetchrow($result);
        $total = $row['total'];
        $this->db->sql_freeresult($result);
        // ,
        $order_by = 'a.id DESC';
        $sql_array = [
            'SELECT'	=> '*',
            'FROM'		=> [ $tbl_main	=> 'a', ],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [
                        $tbl_items => 'b',
                    ],
                    'ON'	=> 'a.id=b.transaction_id',
                ],
                [
                    'FROM'	=> [
                        USERS_TABLE => 'u',
                    ],
                    'ON'	=> 'a.user_id=u.user_id',
                ],
            ],
            // 'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        foreach($rowset as &$row)
        {
            $row['created_time'] = $this->user->format_date($row['created_time']);
        }
        return [$rowset, $total];
    }/*}}}*/


}
