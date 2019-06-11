<?php

namespace jeb\snahp\migrations;

class v_0_25_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_25_0']; }

    public function update_schema()
    {
        return [];
    }

    public function revert_schema()
    {
        return [];
    }

    // Apparently BLOB and TEXT in mysql cannot have default value
    // This was causing problem when registering (inserting) a new user
    // Make a trigger to define a default value before insertion
    public function set_default()
    {
        $sql = '
            CREATE TRIGGER set_default_snp_fav_fid_exclude
            BEFORE INSERT ON `phpbb_users` 
            FOR EACH ROW
                SET NEW.`snp_fav_fid_exclude` = "";
            ';
        $this->db->sql_query($sql);
    }
    public function update_data()

    {
        return [
            [
                'custom',
                [
                    [ $this, 'set_default' ]
                ]
            ]
        ];
    }

}
