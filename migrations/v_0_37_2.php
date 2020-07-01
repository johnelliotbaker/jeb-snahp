<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_37_2 extends container_aware_migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_37_1']; }

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
			's' => [
				'display_on_posting' => false,
				'bbcode_match'       => '[s]{TEXT}[/s]',
				'bbcode_tpl'         => '<span style="text-decoration: line-through;">{TEXT}</span>',
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
