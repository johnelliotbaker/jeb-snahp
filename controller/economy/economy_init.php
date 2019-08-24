<?php
namespace jeb\snahp\controller\economy;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Economy :: MCP :: Intialize Economy
 * */

class economy_init
{
    protected $db;
    protected $tbl;
    public function __construct(
        $db,
        $tbl
    )/*{{{*/
    {
        $this->db = $db;
        $this->tbl = $tbl;
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        switch ($mode)
        {
        case 'initialize_database':
            return $this->init();
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    private function init()/*{{{*/
    {
        $data = [
            'type'         => 'invitation_points',
            'display_name' => 'Invitation Points',
            'sell_unit'    => 'IP',
            'buy_unit'     => '$',
            'buy_rate'     => 200000,
            'sell_rate'    => 100000,
            'enable'       => 1
        ];
        $sql = 'INSERT INTO ' . $this->tbl['bank_exchange_rates'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        $data = [
            'name'         => 'custom_rank',
            'display_name' => 'Custom Rank I',
            'description'  => 'Create custom rank',
            'price'        => '100000',
            'value'        => '1',
            'unit'         => '',
            'lifespan'     => '0',
            'img_url'      => '',
            'help_url'     => '/app.php/snahp/help/docs/?name=ucp_custom_rank',
            'max_per_user' => '1',
            'enable'       => '1',
        ];
        $sql = 'INSERT INTO ' . $this->tbl['mrkt_product_classes'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        $data = [
            'name'         => 'search_cooldown_reducer',
            'display_name' => 'Search Interval Reducer I',
            'description'  => 'Reduce the time between consecutive searches',
            'price'        => '10000',
            'value'        => '1',
            'unit'         => 'sec',
            'lifespan'     => '0',
            'img_url'      => '',
            'help_url'     => '',
            'max_per_user' => '20',
            'enable'       => '1',
        ];
        $sql = 'INSERT INTO ' . $this->tbl['mrkt_product_classes'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        trigger_error('Economy Initialized');
    }/*}}}*/

}
