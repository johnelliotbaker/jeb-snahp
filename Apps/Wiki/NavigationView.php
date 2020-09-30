<?php

namespace jeb\snahp\Apps\Wiki;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/Wiki/Models/ArticleGroup.php';
require_once 'ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Views\SortByPriorityMixin;
use jeb\snahp\core\Rest\Permissions\UserPermission;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;
use jeb\snahp\core\Rest\Permissions\AllowNonePermission;
use \R as R;

class NavigationView extends ListCreateAPIView
{
    use SortByPriorityMixin;

    const ARTICLE_GROUP_TABLE = 'phpbb_snahp_wiki_article_group';
    const ARTICLE_ENTRY_TABLE = 'phpbb_snahp_wiki_article_entry';

    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
    protected $request;
    protected $sauth;
    protected $model;

    public function __construct($request, $sauth, $model, $permissionModel)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;
        $this->permissionClasses = [new AllowNonePermission()];
        $this->permission = new NavPermission($permissionModel, $sauth);
    }

    public function view()
    {
        return parent::dispatch();
    }

    public function filterQueryset($queryset)
    {
        $serializer = $this->getSerializer($queryset);
        return $this->permission->filter($this->request, $serializer->data());
    }

    public function list($request)
    {
        // checkPermissions is not called in navigation view
        // Instead, filterQueryset filters for permitted article groups
        $groups = $this->filterQueryset($this->getQueryset());
        $data = [];
        foreach ($groups as $group) {
            $results = $this->makeGroup($group);
            if ($results || $this->sauth->user_belongs_to_groupset(null, 'Keepers')) {
                $data[] = array_merge($group->export(), ['results' => $results]);
            }
        }
        return new JsonResponse($data);
    }

    public function makeGroup($group)
    {
        $name = $group->name;
        $id = $group->id;
        $group = R::load($this::ARTICLE_GROUP_TABLE, $id);
        $entries = getOwnList($group->with(' ORDER BY priority DESC, subject ASC'), $this::ARTICLE_ENTRY_TABLE);
        $entries = array_map(
            function ($arg) use ($name) {
                $data['id'] = $arg->id;
                $data['subject'] = $arg->subject;
                $data['group'] = $name;
                return $data;
            },
            $entries
        );
        return array_values($entries);
    }
}


class NavPermission extends UserPermission
{
    public function __construct($permissionModel, $sauth)
    {
        $this->sauth = $sauth;
        parent::__construct($permissionModel, $sauth->userId);
    }

    public function filter($request, $articleGroups)
    {
        if ($this->sauth->is_dev()) {
            return $articleGroups;
        }
        $perms = $this->getPermissions();
        return array_filter(
            $articleGroups,
            function ($articleGroup) use ($perms, $request) {
                $targetCodename = $this->makePermissionCodename($request, 'wiki', $articleGroup->name);
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
            }
        );
    }
}
