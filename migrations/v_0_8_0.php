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

class v_0_8_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_7_0'];
    }

    public function update_schema()
    {
        $time = time();
        $sevendays = 60 * 60 * 24 * 7;
        $reset_time = $time + $sevendays;
        return [
            "add_columns" => [
                $this->table_prefix . "users" => [
                    "snp_req_dib_enable" => ["TINT:2", 1],
                ],
                $this->table_prefix . "groups" => [
                    "snp_req_n_base" => ["UINT", 2],
                    "snp_req_n_cycle" => ["UINT", 10],
                    "snp_req_n_offset" => ["INT:3", 1],
                    "snp_req_b_nolimit" => ["BOOL", 0],
                    "snp_req_b_give" => ["BOOL", 0],
                    "snp_req_b_take" => ["BOOL", 0],
                    "snp_req_b_active" => ["BOOL", 1],
                ],
            ],
            "add_tables" => [
                $this->table_prefix . "snahp_dibs" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "tid" => ["INT:10", null],
                        "dibber_uid" => ["INT:10", null],
                        "undibber_uid" => ["INT:10", null],
                        "dib_time" => ["INT:11", null],
                        "undib_time" => ["INT:11", null],
                        "status" => ["TINT:2", 1],
                    ],
                    "PRIMARY_KEY" => "id",
                    "KEYS" => [
                        "tid" => ["INDEX", "tid"],
                        "dibber_uid" => ["INDEX", "dibber_uid"],
                    ],
                ],
                $this->table_prefix . "snahp_request" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "tid" => ["INT:10", null],
                        "pid" => ["INT:10", null],
                        "fid" => ["UINT", null],
                        "requester_uid" => ["INT:10", null],
                        "requester_username" => ["VCHAR", ""],
                        "requester_colour" => ["VCHAR:6", ""],
                        "fulfiller_uid" => ["INT:10", null],
                        "fulfiller_username" => ["VCHAR", ""],
                        "fulfiller_colour" => ["VCHAR:6", ""],
                        "created_time" => ["INT:11", null],
                        "commit_time" => ["INT:11", null],
                        "fulfilled_time" => ["INT:11", null],
                        "solved_time" => ["INT:11", null],
                        "status" => ["TINT:2", 1],
                    ],
                    "PRIMARY_KEY" => "id",
                    "KEYS" => [
                        "tid" => ["UNIQUE", "tid"],
                        "requester_uid" => ["INDEX", "requester_uid"],
                        "fulfiller_uid" => ["INDEX", "fulfiller_uid"],
                    ],
                ],
                $this->table_prefix . "snahp_request_users" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user_id" => ["INT:10", null],
                        "n_use" => ["UINT", 0],
                        "n_use_this_cycle" => ["UINT", 0],
                        "n_offset" => ["INT:3", 1],
                        "start_time" => ["INT:11", $time],
                        "cycle_deltatime" => ["INT:11", $sevendays],
                        "reset_time" => ["INT:11", $reset_time],
                        "b_nolimit" => ["BOOL", 0],
                        "b_give" => ["BOOL", 0],
                        "b_take" => ["BOOL", 0],
                        "b_active" => ["BOOL", 1],
                        "b_active_override" => ["BOOL", 0],
                        "ban_msg" => ["VCHAR", ""],
                        "status" => ["TINT:2", 1],
                    ],
                    "PRIMARY_KEY" => "id",
                    "KEYS" => [
                        "user_id" => ["UNIQUE", "user_id"],
                    ],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "groups" => [
                    "snp_req_n_base",
                    "snp_req_n_cycle",
                    "snp_req_n_offset",
                    "snp_req_b_nolimit",
                    "snp_req_b_give",
                    "snp_req_b_take",
                    "snp_req_b_active",
                ],
                $this->table_prefix . "users" => ["snp_req_dib_enable"],
            ],
            "drop_tables" => [
                $this->table_prefix . "snahp_dibs",
                $this->table_prefix . "snahp_request",
                $this->table_prefix . "snahp_request_users",
            ],
        ];
    }

    public function update_data()
    {
        $sevendays = 60 * 60 * 24 * 7;
        $threedays = 60 * 60 * 24 * 3;
        return [
            ["config.add", ["snp_b_request", 1]],
            ["config.add", ["snp_req_cycle_time", $sevendays]],
            ["config.add", ["snp_req_redib_cooldown_time", $threedays]],
            ["config.add", ["snp_req_fid", "5,16,67,17,68,69,18"]],
            [
                "module.add",
                [
                    "acp",
                    "ACP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\acp\main_module",
                        "modes" => ["request"],
                    ],
                ],
            ],
        ];
    }
}
