<?php

namespace jeb\snahp\Apps\Wiki\Models;

require_once 'ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\CommaSeparatedPositiveIntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class Group extends Model
{
    const TABLE_NAME = 'phpbb_snahp_wiki_group';

    protected $fields;
    protected $requiredFields = ['name'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'name' => new StringField(),
        ];
    }

    public function performPreCreate($instance)/*{{{*/
    {
        global $user;
        $instance->author = $user->data['user_id'];
    }/*}}}*/
}
