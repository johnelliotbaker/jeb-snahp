<?php

// Foe Blocking

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_35_2 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_35_1'];
    }

    public function update_schema()
    {
        return [ ];
    }

    public function revert_schema()
    {
        return [ ];
    }

    public function install_bbcodes()
    {
        $install = new bbcodes_installer($this->db, $this->container->get('request'), $this->container->get('user'), $this->phpbb_root_path, $this->php_ext);
        $install->install_bbcodes([
            'himg' => [
                'display_on_posting' => false,
                'bbcode_match'       => '[himg={NUMBER1}]{URL}[/himg]',
                'bbcode_tpl'         => '<img src="{URL}" style="max-height: {NUMBER1}px;" />',
            ],
            'wimg' => [
                'display_on_posting' => false,
                'bbcode_match'       => '[wimg={NUMBER1}]{URL}[/wimg]',
                'bbcode_tpl'         => '<img src="{URL}" style="max-width: {NUMBER1}px;" />',
            ],
            'nurl' => [
                'display_on_posting' => false,
                'bbcode_match'       => '[nurl={URL}]{TEXT}[/nurl]',
                'bbcode_tpl'         => '<a href="{URL}" class="postlink nurl">{TEXT}</a>',
            ],
        ]);
    }

    public function update_data()
    {
        return [
            ['custom', [[$this, 'install_bbcodes']]],
        ];
    }
}
