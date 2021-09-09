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

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_5_0'];
    }

    public function update_schema()
    {
    }

    public function revert_schema()
    {
    }

    public function update_data()
    {
        return [["config.add", ["snp_b_notify_op_on_report", 1]]];
    }
}
