<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_34_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_33_1'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'snahp_log' => [
                    'target_id' => ['INT:11', 0],
                    'data' => ['MTEXT_UNI', ''],
                ],
            ],
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
                        'allow_viewtopic' => ['BOOL', 0],
                        'allow_reply'     => ['BOOL', 0],
                        'allow_quote'     => ['BOOL', 0],
                        'allow_pm'        => ['BOOL', 0],
                        'mod_id'     => ['INT:11', 0],
                        'mod_reason' => ['VCHAR:512', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_unique_index'    => [
                $this->table_prefix . 'snahp_foe' => [
                    'id_pair' => ['blocker_id', 'blocked_id'],
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_foe' => [
                    'blocker_id' => ['blocker_id'],
                    'blocked_id' => ['blocked_id'],
                    'post_id'    => ['post_id'],
                    'status'    => ['status'],
                ],
                $this->table_prefix . 'snahp_log' => [
                    'name' => ['name'],
                    'target_id' => ['target_id'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                $this->table_prefix . 'snahp_log' => [ 'data', 'target_id'],
            ],
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
            ['module.add', [
                'ucp',
                'UCP_SNP_TITLE', [
                    'module_basename' => '\jeb\snahp\ucp\main_module',
                    'modes'           => [ 'block', ],
                ],
            ]],
            // ['custom', [[$this, 'set_utf8mb4']]],
        ];
    }
}
