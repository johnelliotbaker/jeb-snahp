<?php

namespace jeb\snahp\migrations;

class v_0_20_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_19_2']; }

    public function update_schema()
    {
        return [
            'add_columns'	=> [
                GROUPS_TABLE  => [
                    'snp_search_index_b_enable' => ['BOOL', 0],
                ],
            ],
            'add_tables' => [
                $this->table_prefix . 'snahp_manual_search_posts'	=> [
                    'COLUMNS' => [
                        'id'  => ['UINT', null, 'auto_increment'],
                        'post_id' => ['INT:10', null],
                        'user_id' => ['INT:10', null],
                        'username_clean' => ['VCHAR:20', null],
                        'index_time' => ['INT:11', null],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_unique_index' => [
                $this->table_prefix . 'snahp_manual_search_posts' => [
                    'post_id'	=> ['post_id'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns'	=> [
                GROUPS_TABLE  => [
                    'snp_search_index_b_enable',
                ],
            ],
            'drop_tables' => [
                $this->table_prefix . 'snahp_manual_search_posts',
            ],
        ];
    }

    public function update_data()
    {
    }

}
