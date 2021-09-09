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
        return $this->db_tools->sql_table_exists($this->table_prefix . "snahp");
    }

    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v31x\v314'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp" => [
                    "COLUMNS" => [
                        "sid" => ["UINT", null, "auto_increment"],
                    ],
                    "PRIMARY_KEY" => "sid",
                ],
            ],
            "add_columns" => [
                $this->table_prefix . "users" => [
                    "snp_disable_avatar_thanks_link" => ["UINT", 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [$this->table_prefix . "snahp"],
            "drop_columns" => [
                $this->table_prefix . "users" => [
                    "snp_disable_avatar_thanks_link",
                ],
            ],
        ];
    }
}
