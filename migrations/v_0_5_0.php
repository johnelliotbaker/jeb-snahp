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

class v_0_5_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_4_0',
        );
    }

    public function update_data()
    {
        $anime = [13, 60,];
        $listing = [4, 9, 10, 11, 12, 13, 14, 15, 24, 25, 26, 27, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,];
        $book = [15, 37, 38, 39, 43, 44, 59];
        return array(
            array('config.add', array('snp_pg_fid_anime',   implode(',', $anime))),
            array('config.add', array('snp_pg_fid_listing', implode(',', $listing))),
            array('config.add', array('snp_pg_fid_book',    implode(',', $book))),
        );
    }
}
