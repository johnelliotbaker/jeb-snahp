<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_38_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_37_2'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'miniboard_miniforums' => [
                    'COLUMNS' => [
                        'id'         => ['UINT', null, 'auto_increment'],
                        'mainpost'   => ['INT:11', 0],
                        'owner'      => ['INT:11', 0],
                        'moderators' => ['VCHAR:191', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                $this->table_prefix . 'miniboard_minitopics' => [
                    'COLUMNS' => [
                        'id'       => ['UINT', null, 'auto_increment'],
                        'subject'  => ['VCHAR:191', ''],
                        'created'  => ['INT:11', 0],
                        'modified' => ['INT:11', 0],
                        'author'   => ['INT:11', 0],
                        'status'   => ['VCHAR:191', 'New'],
                        $this->table_prefix . 'miniboard_miniforums_id' => ['INT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_unique_index'    => [
                $this->table_prefix . 'miniboard_miniforums' => [
                    'mainpost' => ['mainpost'],
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'miniboard_minitopics' => [
                    $this->table_prefix . 'miniboard_miniforums_id' => [$this->table_prefix . 'miniboard_miniforums_id'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'miniboard_minitopics',
                $this->table_prefix . 'miniboard_miniforums',
            ],
        ];
    }

    public function set_utf8mb4()
    {
        global $table_prefix, $db;
        $sql = 'ALTER TABLE ' . $table_prefix . 'miniboard_miniforums CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        $db->sql_query($sql);
        $sql = 'ALTER TABLE ' . $table_prefix . 'miniboard_minitopics CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        $db->sql_query($sql);
    }

    public function install_bbcodes()
    {
        $install = new bbcodes_installer($this->db, $this->container->get('request'), $this->container->get('user'), $this->phpbb_root_path, $this->php_ext);
        $install->install_bbcodes(
            [
            's' => [
                'display_on_posting' => false,
                'bbcode_match'       => '[miniboard][/miniboard]',
                'bbcode_tpl'         => '<div class="rx_mini_board"></div>',
            ],
            ]
        );
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_rxn_b_master', 1]],
            ['custom', [[$this, 'install_bbcodes']]],
            ['custom', [[$this, 'set_utf8mb4']]],
        ];
    }
}
