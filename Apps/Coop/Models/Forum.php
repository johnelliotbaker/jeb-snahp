<?php
namespace jeb\snahp\Apps\Coop\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;

class Forum extends Model
{
    const TABLE_NAME = TABLE_COOP_FORUM;

    protected $fields;
    protected $requiredFields = ["phpbb_post"];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            "phpbb_post" => new IntegerField(),
        ];
    }

    public function createWithPostId($postId)
    {
        $data = [
            "phpbb_post" => (int) $postId,
        ];
        try {
            return $this->create($data);
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), "SQLSTATE[23000]:")) {
                throw $e;
            }
        }
    }

    public function fromPhpbbPost($postId)
    {
        $postId = (int) $postId;
        return $this->getObject("phpbb_post=?", [$postId]);
    }
}
