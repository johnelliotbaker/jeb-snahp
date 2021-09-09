<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_35_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_34_0'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "rh_topictags_group_map" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "groupname" => ["VCHAR:512", ""],
                        "tagname" => ["VCHAR:512", ""],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
                $this->table_prefix . "rh_topictags_forum_map" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "priority" => ["INT:11", 0],
                        "forum_id" => ["INT:11", 0],
                        "groupname" => ["VCHAR:512", ""],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "rh_topictags_group_map" => [
                    "groupname_tagname" => ["groupname", "tagname"],
                ],
                $this->table_prefix . "rh_topictags_forum_map" => [
                    "forum_id_groupname" => ["forum_id", "groupname"],
                ],
            ],
            "add_index" => [
                $this->table_prefix . "rh_topictags_group_map" => [
                    "groupname" => ["groupname"],
                    "tagname" => ["tagname"],
                ],
                $this->table_prefix . "rh_topictags_forum_map" => [
                    "forum_id" => ["forum_id"],
                    "groupname" => ["groupname"],
                    "priority" => ["priority"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [
                $this->table_prefix . "rh_topictags_forum_map",
                $this->table_prefix . "rh_topictags_group_map",
            ],
        ];
    }

    public function update_data()
    {
        return [
                // ['config.add', ['snp_foe_b_master', 1]],
                // ['module.add', [
                //     'ucp',
                //     'UCP_SNP_TITLE', [
                //         'module_basename' => '\jeb\snahp\ucp\main_module',
                //         'modes'           => [ 'block', ],
                //     ],
                // ]],
                // ['custom', [[$this, 'set_utf8mb4']]],
            ];
    }
}
