<?php

namespace jeb\snahp\Apps\Wiki\Models;

require_once 'ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class History extends Model
{
    const TABLE_NAME = 'phpbb_snahp_wiki_article_entry_history';
    const FOREIGN_NAME = 'phpbb_snahp_wiki_article_entry';

    protected $fields;
    protected $requiredFields = ['text', 'subject',  'author', 'parenthash', 'modified_time'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'text' => new StringField(['default' => '']),
            'subject' => new StringField(['default' => '']),
            'author'  => new IntegerField(['default' => $this->userId]),
            'parenthash' => new StringField(['default' => null]),
            'modified_time' => new IntegerField(['default' => time()]),
        ];
    }
}
