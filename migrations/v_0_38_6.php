<?php

// [request] bbcode

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_38_6 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_38_5'];
    }

    public function update_schema()
    {
        return [];
    }

    public function revert_schema()
    {
        return [];
    }

    public function install_bbcodes()
    {
        $install = new bbcodes_installer($this->db, $this->container->get('request'), $this->container->get('user'), $this->phpbb_root_path, $this->php_ext);
        $install->install_bbcodes(
            [
                'request' => [
                    'display_on_posting' => false,
                    'bbcode_match'       => '[request]{TEXT}[/request]',
                    'bbcode_tpl'         => '<request>{TEXT}</request>',
                ]
            ]
        );
    }

    public function update_data()
    {
        return [
            ['custom', [[$this, 'install_bbcodes']]],
        ];
    }
}
