<?php
namespace jeb\snahp\Apps\Wiki;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class SetupDatabaseView
{
    protected $db;
    protected $user;
    protected $request;
    protected $sauth;
    public function __construct(
        $db,
        $user,
        $request,
        $sauth,
        $ArticleEntry,
        $History,
        $ArticleGroup,
        $GroupPermission
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->ArticleEntry = $ArticleEntry;
        $this->History = $History;
        $this->ArticleGroup = $ArticleGroup;
        $this->GroupPermission = $GroupPermission;
        $this->userId = (int) $this->user->data['user_id'];
        $this->shortString = str_repeat('x', 190);
        $this->longString = str_repeat('x', 100000);
        $this->sauth->reject_non_dev('Error Code: ec13cb473a');
    }

    public function view()
    {
        R::freeze(false);
        $this->setupArticleEntry();
        $this->setupHistory();
        $this->setupArticleGroup();
        $this->setupGroupPermission();
        R::freeze(true);
        return new Response('', 200);
    }

    public function setupArticleEntry()
    {
        $model = $this->ArticleEntry;
        $model->wipe();
        $model->create(
            [
            'author'  => 0,
            'subject' => $this->shortString,
            'text'    => $this->longString,
            'created_time' => 1111111111,
            'modified_time' => 1111111111,
            'hidden'  => false,
            'priority'  => 999,
            'hash'  => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'phpbb_snahp_wiki_article_group_id' => 999,
            ]
        );
        $res = R::getWriter()->addUniqueIndex($model::TABLE_NAME, ['subject']);
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'priority', 'priority');
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'phpbb_snahp_wiki_article_group_id', 'phpbb_snahp_wiki_article_group_id');
        $model->wipe();
    }

    public function setupHistory()
    {
        $model = $this->History;
        $model->wipe();
        $model->create(
            [
            "author" => 999,
            "text" => $this->longString,
            "subject" => $this->shortString,
            "parenthash" => $this->shortString,
            "modified_time" => 999,
            'phpbb_snahp_wiki_article_entry_id' => 999,
            ]
        );
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'author', 'author');
        $model->wipe();
    }

    public function setupArticleGroup()
    {
        $model = $this->ArticleGroup;
        $model->wipe();
        $model->create(
            [
            'name' => $this->shortString,
            'title' => $this->shortString,
            'priority'  => 999,
            ]
        );
        $res = R::getWriter()->addUniqueIndex($model::TABLE_NAME, ['name']);
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'priority', 'priority');
        $model->wipe();
    }

    public function setupGroupPermission()
    {
        $model = $this->GroupPermission;
        $model->wipe();
        $model->create(
            [
            'codename'  => $this->shortString,
            'user_group' => 999,
            ]
        );
        $res = R::getWriter()->addUniqueIndex($model::TABLE_NAME, ['codename', 'user_group']);
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'user_group', 'user_group');
        $model->wipe();
    }
}
