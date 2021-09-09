<?php

namespace jeb\snahp\Apps\Wiki;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;

class GroupPermissionListCreateAPIView extends ListCreateAPIView
{
    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
    protected $request;
    protected $sauth;
    protected $model;

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;
        $this->permissionClasses = [
            new AllowDevPermission($sauth),
            // new AllowAnyPermission($sauth),
        ];
    }

    public function createMany()
    {
        // Facilitate mass creation of permissions
        function makeCodenameList($data)
        {
            $res = [];
            foreach ($data['userGroups'] as $userGroup) {
                foreach ($data['actions'] as $action) {
                    foreach ($data['resourceNames'] as $resourceName) {
                        $res[] = ['codename' => "wiki.${action}__${resourceName}", 'user_group' => $userGroup];
                    }
                }
            }
            return $res;
        }
        $requiredFields = ['userGroups', 'actions', 'resourceNames'];
        $data = getRequestData($this->request);
        $missingFields = implode(', ', array_diff($requiredFields, array_keys($data)));
        if ($diff) {
            throw new \Exception("Missing $diff. Error Code: e61bfc1321");
        }
        $iterData = [];
        foreach ($requiredFields as $field) {
            $iterData[$field] = explode(',', $data[$field]);
        }
        $perms = makeCodenameList($iterData);
        foreach ($perms as $perm) {
            $serializer = $this->getSerializer(null, $perm);
            $serializer->fillInitialDataWithDefaultValues();
            if ($serializer->isValid()) {
                $instance = $this->performCreate($serializer);
            }
        }
        return new Response($instance, 201);
    }

    public function list($request)
    {
        $this->checkPermissions($request, $this->sauth->userId);
        $queryset = $this->filterQueryset($this->getQueryset());
        $page = $this->paginateQueryset($queryset);
        if ($page) {
            $serializer = $this->getSerializer($page->objectList);
            return $this->getPaginatedResponse($serializer->data());
        }
        $serializer = $this->getSerializer($queryset);
        $data = $serializer->data();
        $this->appendUserGroupName($data);
        usort(
            $data,
            function ($a, $b) {
                return ($a->user_group < $b->user_group) ? -1 : 1;
            }
        );
        return new JsonResponse($data);
    }

    public function appendUserGroupName($data)
    {
        global $db;
        $sql = 'SELECT group_id, group_name from ' . GROUPS_TABLE . ' ORDER BY group_id ASC';
        $result = $db->sql_query($sql);
        $groups = $db->sql_fetchrowset($result);
        $db->sql_freeresult($result);
        $cache = [];
        foreach ($groups as $group) {
            $cache[$group['group_id']] = $group['group_name'];
        }
        foreach ($data as $entry) {
            $entry->user_group_name = getDefault($cache, $entry->user_group, 0);
        }
    }
}
