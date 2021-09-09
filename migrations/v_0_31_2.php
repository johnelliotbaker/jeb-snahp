<?php

/**
 * Create bank/shop transactions
 * */

namespace jeb\snahp\migrations;

class v_0_31_2 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_31_0'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                USERS_TABLE => [
                    'snp_log_visit_timestamps'  => ['VCHAR:1000', '0,0,0,0,0,0,0,0,0,0'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns'	=> [
                USERS_TABLE => [
                    'snp_log_visit_timestamps',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_log_b_user_spam', 1]],
            ['config.add', ['snp_log_user_spam_interval', 10]],
            ['config.add', ['snp_log_user_spam_buffer_length', 10]],
        ];
    }
}
