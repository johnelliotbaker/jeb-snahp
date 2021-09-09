<?php
namespace jeb\snahp\controller\economy;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class economy_dashboard
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $sauth;
    protected $bank_user_account;
    protected $user_inventory;
    protected $product_class;
    protected $tbl;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $bank_user_account,
        $user_inventory,
        $product_class
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
        $this->bank_user_account = $bank_user_account;
        $this->user_inventory = $user_inventory;
        $this->product_class = $product_class;
        $this->user_id = (int) $this->user->data['user_id'];
        $sauth->reject_non_dev('2cb063ea41');
    }

    public function handle($mode)/*{{{*/
    {
        switch ($mode) {
        case 'overview':
            $cfg['tpl_name'] = '@jeb_snahp/economy/mcp/economy_dashboard/base.html';
            return $this->handle_overview($cfg);
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
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
        foreach ($rowset as &$row) {
            $row['created_time'] = $this->user->format_date($row['created_time']);
        }
        return [$rowset, $total];
    }

    private function get_market_invoice_data_for_pagination($start, $per_page)/*{{{*/
    {
        $tbl_main = $this->tbl['mrkt_invoices'];
        $tbl_items = $this->tbl['mrkt_invoice_items'];
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
                    'FROM'	=> [$tbl_items => 'b'],
                    'ON'	=> 'a.id=b.invoice_id',
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
        foreach ($rowset as &$row) {
            $row['created_time'] = $this->user->format_date($row['created_time']);
            $data = unserialize($row['data']);
            $row['comment'] = isset($data['comment']) ? $data['comment'] : '';
        }
        return [$rowset, $total];
    }

    public function handle_overview($cfg)/*{{{*/
    {
        $type = $this->request->variable('t', 'bank');
        $start = $this->request->variable('start', 0);
        $per_page = $this->request->variable('per_page', 25);
        switch ($type) {
        case 'bank':
            [$rowset, $total] = $this->get_bank_transaction_data_for_pagination($start, $per_page);
            $this->template->assign_var('type', 'bank');
            break;
        case 'market':
            [$rowset, $total] = $this->get_market_invoice_data_for_pagination($start, $per_page);
            $this->template->assign_var('type', 'market');
            break;
        default:
            trigger_error('Invalid type. Error Code: 408d9f2870');
        }
        foreach ($rowset as $row) {
            if (array_key_exists('data', $row)) {
                $data = $row['data'];
                $row['comment'] = $this->get_comment_or_empty($data);
            }
            $this->template->assign_block_vars('T', $row);
        }
        $pg = new \jeb\snahp\core\pagination();
        $base_url = '/app.php/snahp/economy/mcp/dashboard/overview/?t=' . $type;
        $pagination = $pg->make($base_url, $total, $per_page, $start);
        $this->template->assign_vars([
            'PAGINATION' => $pagination,
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Snahp Economy Dashboard');
    }

    private function get_comment_or_empty($data)/*{{{*/
    {
        $data = unserialize($data);
        $comment = isset($data['comment']) ? $data['comment'] : '';
        return $comment;
    }
}
