<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\migrations;

class v_0_8_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_7_0',
        );
    }

    public function update_schema()
    {
        $time = time();
        $sevendays = 60*60*24*7;
        $reset_time = $time + $sevendays;
        return array(
            'add_columns'	=> array(
                $this->table_prefix . 'users'  => array(
                    'snp_req_dib_enable'  => array('TINT:2', 1),
                ),
                $this->table_prefix . 'groups'  => array(
                    'snp_req_n_base'    => array('UINT', 2),
                    'snp_req_n_cycle'   => array('UINT', 10),
                    'snp_req_n_offset'  => array('INT:3', 1),
                    'snp_req_b_nolimit' => array('BOOL', 0),
                    'snp_req_b_give'    => array('BOOL', 0),
                    'snp_req_b_take'    => array('BOOL', 0),
                    'snp_req_b_active'  => array('BOOL', 1),
                ),
            ),
            'add_tables' => array(
                $this->table_prefix . 'snahp_dibs'	=> array(
                    'COLUMNS' => array(
                        'id'  => array('UINT', null, 'auto_increment'),
                        'tid' => array('INT:10', null),
                        'dibber_uid'   => array('INT:10', null),
                        'undibber_uid' => array('INT:10', null),
                        'dib_time'     => array('INT:11', null),
                        'undib_time'   => array('INT:11', null),
                        'status'       => array('TINT:2', 1),
                    ),
                    'PRIMARY_KEY' => 'id',
                    'KEYS' => array(
                        'tid'        => array('INDEX', 'tid'),
                        'dibber_uid' => array('INDEX', 'dibber_uid'),
                    )
                ),
                $this->table_prefix . 'snahp_request'	=> array(
                    'COLUMNS' => array(
                        'id'  => array('UINT', null, 'auto_increment'),
                        'tid' => array('INT:10', null),
                        'pid' => array('INT:10', null),
                        'fid' => array('UINT', null),
                        'requester_uid'      => array('INT:10', null),
                        'requester_username' => array('VCHAR', ''),
                        'requester_colour'   => array('VCHAR:6', ''),
                        'fulfiller_uid'      => array('INT:10', null),
                        'fulfiller_username' => array('VCHAR', ''),
                        'fulfiller_colour'   => array('VCHAR:6', ''),
                        'created_time'       => array('INT:11', null),
                        'commit_time'        => array('INT:11', null),
                        'fulfilled_time'     => array('INT:11', null),
                        'solved_time'        => array('INT:11', null),
                        'status'             => array('TINT:2', 1),
                    ),
                    'PRIMARY_KEY' => 'id',
                    'KEYS' => array(
                        'tid' => array('UNIQUE', 'tid'),
                        'requester_uid' => array('INDEX', 'requester_uid'),
                        'fulfiller_uid' => array('INDEX', 'fulfiller_uid'),
                    )
                ),
                $this->table_prefix . 'snahp_request_users'	=> array(
                    'COLUMNS'     => array(
                        'id'      => array('UINT', null, 'auto_increment'),
                        'user_id' => array('INT:10', null),
                        'n_use'             => array('UINT', 0),
                        'n_use_this_cycle'  => array('UINT', 0),
                        'n_offset'          => array('INT:3', 1),
                        'start_time'        => array('INT:11', $time),
                        'cycle_deltatime'   => array('INT:11', $sevendays),
                        'reset_time'        => array('INT:11', $reset_time),
                        'b_nolimit'         => array('BOOL', 0),
                        'b_give'            => array('BOOL', 0),
                        'b_take'            => array('BOOL', 0),
                        'b_active'          => array('BOOL', 1),
                        'b_active_override' => array('BOOL', 0),
                        'ban_msg'           => array('VCHAR', ''),
                        'status'            => array('TINT:2', 1),
                    ),
                    'PRIMARY_KEY' => 'id',
                    'KEYS' => array(
                        'user_id' => array('UNIQUE', 'user_id'),
                    )
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_columns'	=> array(
                $this->table_prefix . 'groups'  => array(
                    'snp_req_n_base',
                    'snp_req_n_cycle',
                    'snp_req_n_offset',
                    'snp_req_b_nolimit',
                    'snp_req_b_give',
                    'snp_req_b_take',
                    'snp_req_b_active',
                ),
            ),
            'drop_tables' => array(
                $this->table_prefix . 'snahp_dibs',
                $this->table_prefix . 'snahp_request',
                $this->table_prefix . 'snahp_request_users',
            ),
        );
    }

    public function update_data()
    {
        $sevendays = 60*60*24*7;
        $threedays = 60*60*24*3;
        return array(
            array('config.add', array('snp_b_request', 1)),
            array('config.add', array('snp_req_cycle_time', $sevendays)),
            array('config.add', array('snp_req_redib_cooldown_time', $threedays)),
            array('config.add', array('snp_req_fid', '5,16,67,17,68,69,18')),
            array('module.add', array(
                'acp',
                'ACP_SNP_TITLE',
                array(
                    'module_basename' => '\jeb\snahp\acp\main_module',
                    'modes'           => array('request'),
                ),
            )),
        );
    }
}
