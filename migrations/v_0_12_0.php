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

class v_0_12_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_11_2',
        );
    }

    public function update_data()
    {
        $snp_req_postform_fid = [
            'app' => 16,
            'game' => 67,
            'movie' => 17,
            'tv' => 68,
            'music' => 69,
            'misc' => 18,
        ];
        return array(
            array('config.add', array('snp_req_postform_fid', serialize($snp_req_postform_fid))),
        );
    }

}
