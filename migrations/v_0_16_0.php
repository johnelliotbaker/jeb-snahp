<?php

namespace jeb\snahp\migrations;

class v_0_16_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_15_0'];
    }

    public function update_schema()
    {
        return [
            'add_columns'	=> [
                $this->table_prefix . 'groups'  => [
                    'snp_search_interval' => ['INT:10', 0],
                ],
            ],
            // 'add_tables' => [
            //     $this->table_prefix . 'snahp_bump_topic'	=> [
            //         'COLUMNS' => [
            //             'id'     => ['UINT', null, 'auto_increment' ],
            //             'tid'    => ['INT:10', null],
            //         ],
            //         'PRIMARY_KEY' => 'id',
            //         'KEYS' => [
            //             'tid'           => ['INDEX', 'tid'],
            //         ]
            //     ],
            // ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns'	=> [
                $this->table_prefix . 'groups'  => [
                    'snp_search_interval',
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
            ['config.add', ['snp_search_b_enable', true]],
            // ['config.add', ['snp_easter_chicken_chance', 10000]],
            ['module.add', [
                'acp',
                'ACP_SNP_TITLE',
                [
                    'module_basename'	=> '\jeb\snahp\acp\main_module',
                    'modes'				=> ['group_based_search'],
                ],
            ]],
        ];
    }
}
