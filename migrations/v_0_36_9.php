<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_36_9 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_36_8'];
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
            "discogs" => [
                "display_on_posting" => false,
                "bbcode_match" => "[discogs]{TEXT}[/discogs]",
                "bbcode_tpl" =>
                    '<div class="rx_discogs_template" data-data="{TEXT}"></div>',
            ],
            "discogsi" => [
                "display_on_posting" => false,
                "bbcode_match" => "[discogsi]{TEXT}[/discogsi]",
                "bbcode_tpl" =>
                    '<div style="display:inline-block;" class="rx_discogs_template" data-data="{TEXT}"></div>',
            ],
            "imdb" => [
                "display_on_posting" => false,
                "bbcode_match" => "[imdb]{TEXT}[/imdb]",
                "bbcode_tpl" =>
                    '<div class="rx_imdb_template" data-data="{TEXT}"></div>',
            ],
            "tub" => [
                "display_on_posting" => false,
                "bbcode_match" => "[tub]{TEXT}[/tub]",
                "bbcode_tpl" =>
                    '<div class="rx_snahplyr" data-data="{TEXT}"></div>',
            ],
        ]);
    }

    public function update_data()
    {
        return [["custom", [[$this, "install_bbcodes"]]]];
    }
}
