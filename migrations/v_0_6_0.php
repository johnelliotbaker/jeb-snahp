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

class v_0_6_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_5_0',
        );
    }

    public function update_schema()
    {
    }

    public function revert_schema()
    {
    }

    public function update_data()
    {
        return array(
			array('config.add', array('snp_b_send_noti_to_op', 1)),
        );
    }
}
