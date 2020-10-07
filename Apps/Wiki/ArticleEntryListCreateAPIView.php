<?php

namespace jeb\snahp\Apps\Wiki;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Wiki/Models/ArticleGroup.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Wiki/Models/GroupPermission.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\Apps\Wiki\Models\ArticleGroup;
use jeb\snahp\Apps\Wiki\Models\GroupPermission;
use jeb\snahp\core\Rest\Permissions\UserPermission;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;

class ArticleEntryUserPermission extends UserPermission
{
    public function hasPermission($request, $userId, $kwargs=[])
    {
        // Article group permission controls article entry permission
        // This articleGroupId requires checkPermissions to set
        // articleGroupId in $kwargs
        $articleGroupId = getDefault($kwargs, 'articleGroupId');
        if ($articleGroupId === null) {
            throw new \Exception('articleGroupId is missing. Error Code: 95f861eb9e');
        }
        $perms = $this->getPermissions();
        $articleGroup = (new ArticleGroup())->getObject('id=?', [$articleGroupId]);
        $articleGroupName = $articleGroup->name;
        $targetCodename = $this->makePermissionCodename($request, 'wiki', $articleGroupName);
        foreach ($perms as $permObject) {
            $codename = $permObject->codename;
            $perm = $this->parsePermissionCodename($codename);
            if ($perm['action'] === 'all') {
                $codename = $this->makePermissionCodename($request, 'wiki', $perm['resourceName']);
            }
            if ($codename === $targetCodename) {
                return true;
            }
        }
        return false;
    }
}

class ArticleEntryListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
    protected $request;
    protected $sauth;
    protected $model;
    protected $foreignNameParam = 'articleGroupId';

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;

        $this->permissionClasses = [
            // new AllowAnyPermission($sauth),
            new ArticleEntryUserPermission(new GroupPermission(), $sauth->userId),
            new AllowDevPermission($sauth),
        ];
    }

    public function checkPermissions($request, $userId, $kwargs=[])
    {
        $data = getRequestData($request);
        $method = getRequestMethod($request);
        switch ($method) {
        case 'POST':
        case 'GET':
            $fnp = $this->foreignNameParam;
            if (array_key_exists($fnp, $data)) {
                $kwargs[$fnp] = $data[$fnp];
            }
            break;
        default:
            break;
        }
        parent::checkPermissions($request, $userId, $kwargs);
    }
}
