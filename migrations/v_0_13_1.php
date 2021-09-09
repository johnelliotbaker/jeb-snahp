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

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_13_0'];
    }

    public function update_schema()
    {
        return [
            "add_index" => [
                $this->table_prefix . "topics" => [
                    "topic_views" => ["topic_views"],
                    "topic_time" => ["topic_time"],
                ],
            ],
        ];
    }

    public function update_data()
    {
        $duration = 60 * 60 * 24 * 7;
        return [
            ["config.add", ["snp_ql_fav_limit", 300]],
            ["config.add", ["snp_ql_fav_duration", $duration]],
            ["config.add", ["snp_ql_fav_b_replies", 0]],
            ["config.add", ["snp_ql_fav_b_views", 1]],
            ["config.add", ["snp_ql_fav_b_time", 1]],
            ["config.add", ["snp_ql_thanks_given", 1]],
            ["config.add", ["snp_ql_ucp_bookmark", 1]],
            ["config.add", ["snp_ql_req_open_requests", 1]],
        ];
    }
}
