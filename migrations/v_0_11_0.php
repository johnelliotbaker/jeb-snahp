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

class v_0_11_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_10_0',
        );
    }

    public function update_schema()
    {
        return array(
            'add_columns'	=> array(
                $this->table_prefix . 'groups'  => array(
                    'snp_gamespot_enable'  => array('BOOL', 0),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_columns'	=> array(
                $this->table_prefix . 'groups'  => array(
                    'snp_gamespot_enable',
                ),
            ),
        );
    }

    public function update_data()
    {
        $game = [10, 25, 41, 82];
        return array(
            array('config.add', array('snp_pg_fid_game',    implode(',', $game))),
        );
    }
}
