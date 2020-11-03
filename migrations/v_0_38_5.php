<?php

// Required steps for adding more requests subforums

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_38_5 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_38_4'];
    }

    public function update_schema()
    {
        return [ ];
    }

    public function revert_schema()
    {
        return [ ];
    }

    public function update_data()
    {
        $snp_req_postform_fid = [
            'app' => 16,
            'game' => 67,
            'movie' => 17,
            'tv' => 68,
            'music' => 69,
            'misc' => 18,
            'anime' => 1,
            'ebook' => 1,
        ];
        return array(
            array('config.update', array('snp_req_postform_fid', serialize($snp_req_postform_fid))),
        );
    }
}
