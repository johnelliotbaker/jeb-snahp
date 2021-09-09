<?php

namespace jeb\snahp\migrations;

class v_0_19_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_19_0'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_invite_users' => [
                    'COLUMNS' => [
                        'id'       => ['UINT', null, 'auto_increment' ],
                        'user_id'  => ['INT:10', null],
                        'n_available'     => ['INT:10', 0],
                        'n_total_issued'  => ['INT:10', 0],
                        'b_redeem_enable' => ['BOOL', 1],
                        'b_ban'    => ['BOOL', 0],
                        'n_ban'    => ['INT:10', 0],
                        'ban_time' => ['INT:11', 0],
                        'ban_msg_private' => ['TEXT_UNI', ''],
                        'ban_msg_public'  => ['VCHAR_UNI:255', ''],
                        'create_time'  => ['INT:11', 0],
                        'status'   => ['TINT:2', 0],
                    ],
                    'PRIMARY_KEY' => 'id',
                    'KEYS' => [
                        'user_id'         => ['INDEX', 'user_id'],
                        'n_available'     => ['INDEX', 'n_available'],
                        'n_total_issued'  => ['INDEX', 'n_total_issued'],
                        'b_redeem_enable' => ['INDEX', 'b_redeem_enable'],
                        'b_ban'           => ['INDEX', 'b_ban'],
                        'status'          => ['INDEX', 'status'],
                        'create_time'     => ['INDEX', 'create_time'],
                    ]
                ],
                $this->table_prefix . 'snahp_invite' => [
                    'COLUMNS' => [
                        'id'          => ['UINT', null, 'auto_increment' ],
                        'inviter_id'  => ['INT:10', null],
                        'keyphrase'   => ['VCHAR_UNI:255', ''],
                        'redeemer_id' => ['INT:10', null],
                        'status'      => ['TINT:2', 0],
                        'b_active'    => ['BOOL', 1],
                        'create_time'     => ['INT:11', 0],
                        'redeem_time'     => ['INT:11', 0],
                        'expiration_time' => ['INT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'id',
                    'KEYS' => [
                        'inviter_id'  => ['INDEX', 'inviter_id'],
                        'status'      => ['INDEX', 'status'],
                        'b_active'    => ['INDEX', 'b_active'],
                        'create_time' => ['INDEX', 'create_time'],
                        'redeem_time' => ['INDEX', 'redeem_time'],
                        'keyphrase'   => ['INDEX', 'keyphrase'],
                    ]
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_invite',
                $this->table_prefix . 'snahp_invite_users',
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
