<?php

namespace jeb\snahp\migrations;

class v_0_15_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_14_0'];
    }

    public function update_schema()
    {
        return [
            'add_columns'	=> [
                $this->table_prefix . 'users'  => [
                    'snp_req_n_solve' => ['INT:10', 0],
                    'snp_thanks_b_notification' => ['BOOL', 1],
                ],
            ],
            // 'add_tables' => [
            //     $this->table_prefix . 'snahp_bump_topic'	=> [
            //         'COLUMNS' => [
            //             'id'     => ['UINT', null, 'auto_increment' ],
            //             'tid'    => ['INT:10', null],
            //             'status' => ['TINT:2', 1],
            //             'n_bump' => ['UINT:3', 0],
            //             'moderator_uid' => ['INT:10', null],
            //             'poster_uid'    => ['INT:10', null],
            //             'topic_time'                => ['INT:11', null],
            //             'prev_topic_time'           => ['INT:11', null],
            //             'prev_topic_last_post_time' => ['INT:11', null],
            //         ],
            //         'PRIMARY_KEY' => 'id',
            //         'KEYS' => [
            //             'tid'           => ['INDEX', 'tid'],
            //             'status'        => ['INDEX', 'status'],
            //             'moderator_uid' => ['INDEX', 'moderator_uid'],
            //             'poster_uid'    => ['INDEX', 'poster_uid'],
            //         ]
            //     ],
            // ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns'	=> [
                $this->table_prefix . 'users'  => [
                    'snp_req_n_solve',
                    'snp_thanks_b_notification',
                ],
            ],
            // 'drop_tables' => [
            //     $this->table_prefix . 'snahp_bump_topic',
            // ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_req_b_avatar', true]],
            // ['config.add', ['snp_ql_your_topics', true]],
            // ['config.add', ['snp_easter_b_chicken', true]],
            // ['config.add', ['snp_easter_chicken_chance', 10000]],
            // ['module.add', [
            //     'acp',
            //     'ACP_SNP_TITLE',
            //     [
            //         'module_basename'	=> '\jeb\snahp\acp\main_module',
            //         'modes'				=> ['bump_topic'],
            //     ],
            // ]],
        ];
    }
}
