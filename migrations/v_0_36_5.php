<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_36_5 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_36_4']; }

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
      return [ 
        ['config.add', [ 'snp_spot_b_master', true ]],
        ['config_text.add', [ 'snp_spot_cache', '' ]],
      ];
    }

}
