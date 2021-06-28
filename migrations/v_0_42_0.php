<?php

// User Posting Violation

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_42_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_41_0'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_posting_violation' => [
                    'COLUMNS' => [
                        'id' => ['UINT', null, 'auto_increment'],
                        'user_id' => ['INT:11', 0],
                        'post_id' => ['INT:11', 0],
                        'post_text' => ['MTEXT', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_columns' => [
                TOPICS_TABLE => [
                    'snp_violation' => ['BOOL', 0],
                    'snp_violation_reason' => ['VCHAR:1000', ''],
                ],
                USERS_TABLE => [
                    'snp_violation_count' => ['INT:11', 0],
                ],
            ],
            'add_index' => [
                $this->table_prefix . 'snahp_posting_violation' => [
                    'user_id' => ['user_id'],
                    'post_id' => ['post_id'],
                ],
                TOPICS_TABLE => [
                    'snp_violation' => ['snp_violation'],
                ],
                USERS_TABLE => [
                    'snp_violation_count' => ['snp_violation_count'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_posting_violation',
            ],
            'drop_columns' => [
                TOPICS_TABLE => ['snp_violation', 'snp_violation_reason'],
                USERS_TABLE => ['snp_violation_count',],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
