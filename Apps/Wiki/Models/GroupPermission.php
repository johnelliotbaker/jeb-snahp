<?php

namespace jeb\snahp\Apps\Wiki\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class GroupPermission extends Model
{
    const TABLE_NAME = 'phpbb_snahp_wiki_group_permission';

    protected $fields;
    protected $requiredFields = ['codename', 'user_group'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'codename'  => new StringField(),
            'user_group' => new IntegerField(['default' => 0]),
        ];
    }

    public function getAllowedGroups($userGroups=[])
    {
        $slots = R::genSlots($userGroups);
        $rowset = R::find($this::TABLE_NAME, " user_group IN ($slots)", $userGroups);
        if (!$rowset) {
            return [];
        }
        return $rowset;
        return array_unique(
            array_map(
                function ($arg) {
                    return $arg['codename'];
                },
                $rowset
            )
        );
    }
}
