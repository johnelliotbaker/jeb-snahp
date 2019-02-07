<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\migrations;

class v_0_12_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_12_0',
        );
    }

    public function update_schema()
    {
        return array(
            'add_columns'	=> array(
                $this->table_prefix . 'snahp_request'  => array(
                    'b_graveyard'  => array('BOOL', 0),
                ),
            ),
            'add_index'	=> array(
                $this->table_prefix . 'snahp_request'  => array(
                    'status'      => array('status'),
                    'fid'         => array('fid'),
                    'b_graveyard' => array('b_graveyard'),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_columns'	=> array(
                $this->table_prefix . 'snahp_request'  => array(
                    'b_graveyard',
                ),
            ),
        );
    }

}
