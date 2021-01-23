<?php
namespace jeb\snahp\Apps\Xmas;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class SetupDatabaseView
{
    protected $db;/*{{{*/
    protected $request;
    protected $sauth;
    public function __construct(
        $db,
        $request,
        $sauth,
        $Board,
        $Vote,
        $Config
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->Board = $Board;
        $this->Vote = $Vote;
        $this->Config = $Config;
        $this->shortString = str_repeat('x', 254);
        $this->longString = str_repeat('x', 100000);
        $this->sauth->reject_non_dev('Error Code: a7d8c95d04');
    }/*}}}*/

    public function view()
    {
        R::freeze(false);
        $this->setupEntry();
        R::freeze(true);
        return new Response('', 200);
    }

    public function setupBoard()
    {
        $Model = $this->Board;
        $Model->wipe();
        $Model->create(
            [
            'user' => 999,
            'tiles' => json_encode([]),
            'created' => time(),
            ]
        );
        $res = R::getWriter()->addUniqueIndex($Model::TABLE_NAME, ['user']);
        $Model->wipe();
    }

    public function setupVote()
    {
        $Model = $this->Vote;
        $Model->wipe();
        $Model->create(
            [
            'period' => $this->shortString,
            'created' => 999,
            'user' => 999,
            'tile' => 999,
            ]
        );
        $res = R::getWriter()->addUniqueIndex($Model::TABLE_NAME, ['user', 'period']);
        $res = R::getWriter()->addIndex($Model::TABLE_NAME, 'tile', 'tile');
        $Model->wipe();
    }

    public function setupConfig()
    {
        $Model = $this->Config;
        $Model->wipe();
        $Model->create(
            [
            'name' => $this->shortString,
            'data' => $this->shortString,
            ]
        );
        $Model->wipe();
        $res = R::getWriter()->addIndex($Model::TABLE_NAME, 'name', 'name');
        $data = [
            'rows' => 3,
            'columns' => 3,
            'poolSize' => 13,
        ];
        $Model->create(['name' => 'board', 'data' => json_encode($data)]);
        $start = time();
        $duration = 20;
        $data = [
            'start' => $start,
            'duration' => $duration,
            'end' => $start + $duration,
            'division' => 9,
        ];
        $Model->create(['name' => 'schedule', 'data' => json_encode($data)]);
        $Model->create(['name' => 'votes', 'data' => json_encode([])]);
    }

    public function setupEntry()/*{{{*/
    {
        $this->setupBoard();
        $this->setupVote();
        $this->setupConfig();
    }/*}}}*/
}
