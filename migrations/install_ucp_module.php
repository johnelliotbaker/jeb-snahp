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

class install_ucp_module extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        $sql =
            'SELECT module_id
			FROM ' .
            $this->table_prefix .
            "modules
			WHERE module_class = 'ucp'
				AND module_langname = 'UCP_SNP_TITLE'";
        $result = $this->db->sql_query($sql);
        $module_id = $this->db->sql_fetchfield("module_id");
        $this->db->sql_freeresult($result);

        return $module_id !== false;
    }

    public static function depends_on()
    {
        return ["\jeb\snahp\migrations\install_user_schema"];
    }

    public function update_data()
    {
        return [
            ["module.add", ["ucp", 0, "UCP_SNP_TITLE"]],
            [
                "module.add",
                [
                    "ucp",
                    "UCP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\ucp\main_module",
                        "modes" => ["visibility"],
                    ],
                ],
            ],
        ];
    }
}
