<?php
namespace jeb\snahp\core;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ForumStructureHelper
{
    protected $root_path;
    protected $php_ext;
    public function __construct($root_path, $php_ext)
    {
        $this->root_path = $root_path;
        $this->php_ext = $php_ext;
    }

    public function selectSubforum(
        $parent_id,
        $cooldown = 0,
        $b_immediate = false
    ) {
        include_once $this->root_path . "includes/functions_admin.php";
        $fid = get_forum_branch($parent_id, "children");
        return array_map(function ($array) {
            return $array["forum_id"];
        }, $fid);
    }

    public function makeSubforumSelectorHTML($selectId)
    {
        include_once "includes/functions_admin.php";
        return make_forum_select(
            $select_id = $selectId,
            $ignore_id = false,
            $ignore_acl = false,
            $ignore_nonpost = false,
            $ignore_emptycat = true,
            $only_acl_post = false,
            $return_array = false
        );
    }
}
