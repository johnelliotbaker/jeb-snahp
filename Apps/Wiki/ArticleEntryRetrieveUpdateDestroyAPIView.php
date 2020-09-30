<?php

namespace jeb\snahp\Apps\Wiki;

require_once 'ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once 'ext/jeb/snahp/Apps/Wiki/Models/GroupPermission.php';
require_once 'ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use jeb\snahp\core\Rest\Serializers\Serializer;
use jeb\snahp\core\Rest\Views\RetrieveUpdateDestroyAPIView;
use jeb\snahp\Apps\Wiki\Models\GroupPermission;
use jeb\snahp\core\Rest\Permissions\UserPermission;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;
use jeb\snahp\core\Rest\Fields\BoolField;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\FunctionField;
use jeb\snahp\core\Rest\Fields\UserField;

use \R as R;

class ArticleEntryRetrieveUpdateDestroyAPIView extends RetrieveUpdateDestroyAPIView
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
            // new AllowAnyPermission($sauth),
            new ArticleEntryUserPermission(new GroupPermission(), $sauth->userId),
            new AllowDevPermission($sauth),
        ];
    }

    public function viewByName($articleName)
    {
        $this->serializerClass = __NAMESPACE__ . '\MySerializer';
        $entry = R::findOne('phpbb_snahp_wiki_article_entry', 'subject=?', [$articleName]);
        $g = $entry->phpbb_snahp_wiki_article_group;
        $id = $entry ? $entry->id : 0;
        return parent::dispatch($id);
    }
}

class ArticleEntryUserPermission extends UserPermission
{
    public function hasObjectPermission($request, $userId, $object, $kwargs=[])
    {
        $perms = $this->getPermissions();
        $articleGroup = $object->{'phpbb_snahp_wiki_article_group'};
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

class MySerializer extends Serializer
{
    public $fields = [];

    public function __construct($instance, $data, $kwargs=[])
    {
        parent::__construct($instance, $data, $kwargs);
        $this->fields = [
            'article_group' => new FunctionField(
                function () {
                    return $this->instance->phpbb_snahp_wiki_article_group->id;
                }
            ),
            'subject' => new StringField(),
            'text'    => new StringField(),
            'hash'    => new StringField(),
            'priority'    => new IntegerField(['default' => 500]),
            'created_time' => new IntegerField(['default' => time()]),
            'modified_time' => new IntegerField(['default' => 0]),
            'author' => new UserField(['default' => $instance->author]),
        ];
    }
}

function getLastEditor($articleId)
{
    $articleId = (int) $articleId;
    $history = R::findOne('phpbb_snahp_wiki_article_entry_history', 'phpbb_snahp_wiki_article_entry_id=? ORDER by id DESC', [$articleId]);
    return $history->author;
}
