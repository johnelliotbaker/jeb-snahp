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
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
    }

    public function handle($mode)
    {
        switch ($mode) {
        case 'initialize_database':
            return $this->init();
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }


    private function check_row_exists($data, $id_name, $tbl_name)
    {
        $name = $data[$id_name];
        $sql = 'SELECT * FROM ' . $tbl_name . " WHERE ${id_name}='${name}'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !!$row;
    }
    private function make_row($data, $id_name, $tbl_name)
    {
        $name = $data[$id_name];
        if ($this->check_row_exists($data, $id_name, $tbl_name)) {
            prn("${name} exists.");
        } else {
            prn("Creating ${name}");
            $sql = 'INSERT INTO ' . $tbl_name . $this->db->sql_build_array('INSERT', $data);
            $this->db->sql_query($sql);
        }
    }
    private function init()
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
        $this->make_row($data, 'type', $this->tbl['bank_exchange_rates']);
        $data = [
            'name'         => 'custom_rank',
            'display_name' => 'Custom Rank',
            'description'  => 'Create custom rank',
            'price'        => '100000',
            'value'        => '1',
            'unit'         => '',
            'lifespan'     => '0',
            'img_url'      => 'https://i.imgur.com/Fxqysgt.png',
            'help_url'     => '/app.php/snahp/help/docs/?name=ucp_custom_rank',
            'max_per_user' => '1',
            'enable'       => '1',
        ];
        $this->make_row($data, 'name', $this->tbl['mrkt_product_classes']);
        $data = [
            'name'         => 'search_cooldown_reducer',
            'display_name' => 'Quick Search',
            'description'  => 'Reduce the time between consecutive searches',
            'price'        => '10000',
            'value'        => '1',
            'unit'         => 'sec',
            'lifespan'     => '0',
            'img_url'      => 'https://i.imgur.com/NMGN9wf.png',
            'help_url'     => '',
            'max_per_user' => '20',
            'enable'       => '1',
        ];
        $this->make_row($data, 'name', $this->tbl['mrkt_product_classes']);
        $data = [
            'name'         => 'larger_rep_pool',
            'display_name' => 'Larger Rep. Pool',
            'description'  => 'Increase the number of reputation points you can give away per cycle',
            'price'        => '50000',
            'value'        => '1',
            'unit'         => 'RP',
            'lifespan'     => '0',
            'img_url'      => 'https://i.imgur.com/caQmHjJ.png',
            'help_url'     => '/app.php/snahp/wiki/larger_rep_pool/',
            'max_per_user' => '4',
            'enable'       => '1',
        ];
        $this->make_row($data, 'name', $this->tbl['mrkt_product_classes']);
        trigger_error('Economy Initialized');
    }
}
