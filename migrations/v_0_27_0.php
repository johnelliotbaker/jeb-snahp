<?php

namespace jeb\snahp\migrations;

class v_0_27_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_26_0'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_reputation" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "post_id" => ["INT:11", 0],
                        "poster_id" => ["INT:11", 0],
                        "giver_id" => ["INT:11", 0],
                        "time" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_reputation" => [
                    "post_id" => ["post_id"],
                    "poster_id" => ["poster_id"],
                    "giver_id" => ["giver_id"],
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "snahp_reputation" => [
                    "pair" => ["post_id", "giver_id"],
                ],
            ],
            "add_columns" => [
                USERS_TABLE => [
                    "snp_rep_n_received" => ["INT:11", 0],
                    "snp_rep_n_given" => ["INT:11", 0],
                    "snp_rep_n_available" => ["INT:11", 5],
                    "snp_rep_b_avatar" => ["BOOL", 1],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                USERS_TABLE => [
                    "snp_rep_n_received",
                    "snp_rep_n_given",
                    "snp_rep_n_available",
                    "snp_rep_b_avatar",
                ],
            ],
            "drop_tables" => [$this->table_prefix . "snahp_reputation"],
        ];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_rep_b_master", 1]],
            ["config.add", ["snp_rep_b_avatar", 1]],
            ["config.add", ["snp_rep_b_toplist", 1]],
        ];
    }
}
