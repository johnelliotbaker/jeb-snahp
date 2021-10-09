<?php
namespace jeb\snahp\Apps\Coop\Permissions;
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use jeb\snahp\core\Rest\Permissions\Permission;

class AllowOpPermission extends Permission
{
    public function __construct($sauth)
    {
        $this->sauth = $sauth;
        parent::__construct();
    }

    public function hasPermission($request, $userId, $kwargs = [])
    {
        return true;
    }

    public function hasObjectPermission(
        $request,
        $userId,
        $object,
        $kwargs = []
    ) {
        if ((int) $object->user === $userId) {
            return true;
        }
        return false;
    }
}
