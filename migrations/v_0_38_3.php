<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_38_3 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_38_2'];
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
        $install = new bbcodes_installer(
            $this->db,
            $this->container->get("request"),
            $this->container->get("user"),
            $this->phpbb_root_path,
            $this->php_ext
        );
        $install->install_bbcodes([
            "banner" => [
                "display_on_posting" => false,
                "bbcode_match" => "[banner]{URL}[/banner]",
                "bbcode_tpl" => "",
            ],
            "s" => [
                "display_on_posting" => true,
                "bbcode_match" => "[s]{TEXT}[/s]",
                "bbcode_tpl" =>
                    '<span style="text-decoration: line-through;">{TEXT}</span>',
            ],
        ]);
    }

    public function update_data()
    {
        return [["custom", [[$this, "install_bbcodes"]]]];
    }
}
