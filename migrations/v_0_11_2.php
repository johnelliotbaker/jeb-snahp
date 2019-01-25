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

class v_0_11_2 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_11_0',
        );
    }

    public function update_schema()
    {
        return array(
            'add_index'	=> array(
                $this->table_prefix . 'users'  => array(
                    'snp_req_dib_enable'  => array('snp_req_dib_enable'),
                ),
            ),
        );
    }

}
