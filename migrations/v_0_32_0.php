<?php

/**
 * Wikipedia
 * */

namespace jeb\snahp\migrations;

class v_0_32_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_31_2'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_wiki" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "editor_id" => ["INT:11", 0],
                        "type" => ["VCHAR:255", ""],
                        "name" => ["VCHAR:255", ""],
                        "text" => ["MTEXT_UNI", ""],
                        "b_public" => ["BOOL", 1],
                        "created_time" => ["BINT", 0],
                        "edited_time" => ["BINT", 0],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "snahp_wiki" => [
                    "name" => ["name"],
                ],
            ],
            "add_index" => [],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [$this->table_prefix . "snahp_wiki"],
        ];
    }

    public function set_utf8mb4()
    {
        global $table_prefix, $db;
        $sql =
            "ALTER TABLE " .
            $table_prefix .
            "snahp_wiki CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $db->sql_query($sql);
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_wiki_b_master", 1]],
            ["custom", [[$this, "set_utf8mb4"]]],
        ];
    }
}
