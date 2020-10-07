<?php

/*{{{*/
namespace jeb\snahp\Apps\MiniBoard\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\DateField;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\FunctionField;

use \R as R;

/*}}}*/

class Topic extends Model
{
    const TABLE_NAME = MINITOPICS_TABLE;
    const FOREIGN_NAME = MINIFORUMS_TABLE;

    protected $fields;
    protected $requiredFields = ['subject', 'author', 'status', 'created'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'subject' => new StringField(),
            'author' => new IntegerField(),
            'status' => new StringField(),
            'created' => new DateField(),
            'modified' => new DateField(),
        ];
        $this->query = [
            'statement' => '1=1',
            'data' => [],
        ];
    }

    public function forum($request)/*{{{*/
    {
        $value = $request->variable('miniforum', 0);
        if ($value === 0) {
            return [
                'statement' => '',
                'data' => [],
            ];
        }
        $foreignPkName = $this::FOREIGN_NAME . '_id';
        return [
            'statement' => "AND ${foreignPkName}=:miniforum",
            'data' => [ 'miniforum' => $value],
        ];
    }/*}}}*/

    public function search($request)/*{{{*/
    {
        $value = $request->variable('search', '');
        if ($value === '') {
            return [
                'statement' => '',
                'data' => [],
            ];
        }
        return [
            'statement' => 'AND subject LIKE :search',
            'data' => [ 'search' => '%' . $value . '%', ],
        ];
    }/*}}}*/

    public function status($request)/*{{{*/
    {
        $value = $request->variable('status', '');
        if ($value === '') {
            return [
                'statement' => '',
                'data' => [],
            ];
        }
        return [
            'statement' => 'AND status=:status',
            'data' => [ 'status' => $value],
        ];
    }/*}}}*/

    public function sort($request)/*{{{*/
    {
        $allowedSortKeys = ['id'];
        $sortBy = $request->variable('sortBy', '');
        if (!in_array($sortBy, $allowedSortKeys)) {
            return '';
        }
        $sortOrder = $request->variable('sortOrder', 'DESC');
        $sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';
        if ($sortBy === '') {
            return '';
        }
        return "ORDER BY {$sortBy} {$sortOrder}";
    }/*}}}*/

    public function mergeQuery($newQuery)/*{{{*/
    {
        if (!$newQuery['statement']) {
            return $this->query;
        }
        $this->query['statement'] .= ' ' . $newQuery['statement'];
        $this->query['data'] = array_merge($this->query['data'], $newQuery['data']);
        return $this->query;
    }/*}}}*/

    public function getName($n)/*{{{*/
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        return $randomString;
    }/*}}}*/

    public function filter($request)/*{{{*/
    {
        $forumId = $request->variable('miniforum', 0);
        if (!$forumId) {
            return [];
        }
        $sqls[] = $this->forum($request);
        $sqls[] = $this->search($request);
        $sqls[] = $this->status($request);
        foreach ($sqls as $sql) {
            $this->mergeQuery($sql);
        }
        $sortSnippet = $this->sort($request);
        $result = R::find($this::TABLE_NAME, $this->query['statement'], $this->query['data'], $sortSnippet);
        return $result;
    }/*}}}*/

    public function performPreCreate($instance)/*{{{*/
    {
        # in-place for performance
        global $user;
        $instance->author = $user->data['user_id'];
    }/*}}}*/

    public function makeDummyTopics($request)/*{{{*/
    {
        // for ($i = 0; $i < 1000; $i++) {
        //     $bean = R::xdispense($this::TABLE_NAME);
        //     $bean->phpbb_miniboard_miniforums_id = 4;
        //     $bean->subject = $this->getName(30);
        //     $bean->created = time();
        //     $bean->author = 49;
        //     $bean->status = 'New';
        //     R::store($bean);
        // }
    }/*}}}*/
}
