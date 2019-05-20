<?php

namespace jeb\snahp\migrations;

class v_0_21_3 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_21_0']; }

    public function update_schema()
    {
    }

    public function revert_schema()
    {
    }

    public function update_data()
    {
        $snp_fid =  [
            'requests' => '5',
            'listings' => '4',
            'graveyard' => '23',
        ];
        return [
            [
                'config_text.add', [ 'snp_fid', serialize($snp_fid) ]
            ],
        ];
    }

}
