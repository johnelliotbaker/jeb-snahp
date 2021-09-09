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

class install_user_schema extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'snp_hide_locked');
        return $this->db_tools->sql_table_exists($this->table_prefix . 'snahp');
    }

    public static function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }

    public function update_schema()
    {
        return array(
            'add_tables'		=> array(
                $this->table_prefix . 'snahp'	=> array(
                    'COLUMNS'       => array(
                        'sid'   => array('UINT', null, 'auto_increment'),
                    ),
                    'PRIMARY_KEY'   => 'sid',
                ),
            ),
            'add_columns'	=> array(
                $this->table_prefix . 'users'  => array(
                    'snp_disable_avatar_thanks_link' => array('UINT', 0),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'		=> array(
                $this->table_prefix . 'snahp',
            ),
            'drop_columns'	=> array(
                $this->table_prefix . 'users'  => array(
                    'snp_disable_avatar_thanks_link',
                ),
            ),
        );
    }
}
