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

class v_0_13_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_13_0',
        );
    }

    public function update_schema()
    {
        return array(
            'add_index'	=> array(
                $this->table_prefix . 'topics'  => array(
                    'topic_views' => array('topic_views'),
                    'topic_time'  => array('topic_time'),
                ),
            ),
        );
    }

    public function update_data()
    {
        $duration = 60 * 60 * 24 * 7;
        return array(
            array('config.add', array('snp_ql_fav_limit', 300)),
            array('config.add', array('snp_ql_fav_duration', $duration)),
            array('config.add', array('snp_ql_fav_b_replies', 0)),
            array('config.add', array('snp_ql_fav_b_views', 1)),
            array('config.add', array('snp_ql_fav_b_time', 1)),
            array('config.add', array('snp_ql_ucp_bookmark', 1)),
            array('config.add', array('snp_ql_req_open_requests', 1)),
        );
    }

}
