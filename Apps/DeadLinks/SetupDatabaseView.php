<?php
namespace jeb\snahp\Apps\DeadLinks;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class SetupDatabaseView
{
    protected $db;
    protected $request;
    protected $sauth;
    public function __construct(
        $db,
        $request,
        $sauth,
        $Entry
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->Entry = $Entry;
        $this->shortString = str_repeat('x', 254);
        $this->longString = str_repeat('x', 100000);
        $this->sauth->reject_non_dev('Error Code: a7d8c95d04');
    }

    public function view()
    {
        R::freeze(false);
        $this->setupEntry();
        R::freeze(true);
        return new Response('', 200);
    }

    public function setupEntry()
    {
        $model = $this->Entry;
        $model->wipe();
        $model->create(
            [
            'topic'  => 999,
            'type' => $this->shortString,
            'subtype'    => $this->shortString,
            'comment' => $this->shortString,
            'status' => $this->shortString,
            'user'  => 999,
            'created'  => 999,
            'modified'  => 999,
            ]
        );
        // $res = R::getWriter()->addUniqueIndex($model::TABLE_NAME, ['subject']);
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'topic', 'topic');
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'status', 'status');
        $res = R::getWriter()->addIndex($model::TABLE_NAME, 'type', 'type');
        $model->wipe();
    }
}
