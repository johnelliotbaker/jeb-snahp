<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_34_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_33_1']; }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_foe' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'status'       => ['TINT:2', 0],
                        'blocker_id'   => ['INT:11', 0],
                        'blocked_id'   => ['INT:11', 0],
                        'post_id'      => ['INT:11', 0],
                        'created_time' => ['INT:11', 0],
                        'duration'     => ['INT:11', 0],
                        'block_reason' => ['VCHAR:512', ''],
                        'mod_id'       => ['INT:11', 0],
                        'mod_reason'   => ['VCHAR:512', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_unique_index'    => [
                $this->table_prefix . 'snahp_foe' => [
                    // 'id_pair' => ['blocker_id', 'blocked_id'],
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_foe' => [
                    'blocker_id' => ['blocker_id'],
                    'blocked_id' => ['blocked_id'],
                    'post_id'    => ['post_id'],
                    'status'    => ['status'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_foe',
            ],
        ];
    }

    public function set_utf8mb4()
    {
        global $table_prefix, $db;
        $sql = 'ALTER TABLE ' . $table_prefix . 'snahp_wiki CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        $db->sql_query($sql);
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_foe_b_master', 1]],
            // ['custom', [[$this, 'set_utf8mb4']]],
        ];
    }

}
