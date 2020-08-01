<?php

namespace jeb\snahp\Apps\Wiki\Models;

require_once 'ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class History extends Model
{
    const TABLE_NAME = 'phpbb_snahp_wiki_entry_history';
    const FOREIGN_NAME = 'phpbb_snahp_wiki_entry';

    protected $fields;
    protected $requiredFields = ['patch'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'patch' => new StringField(),
            'author' => new StringField(),
        ];
        $this->query = [
            'statement' => '1=1',
            'data' => [],
        ];
    }

    public function entry($request)/*{{{*/
    {
        $value = $request->variable('entry', 0);
        if ($value === 0) {
            return [
                'statement' => '',
                'data' => [],
            ];
        }
        $foreignPkName = $this::FOREIGN_NAME . '_id';
        return [
            'statement' => "AND ${foreignPkName}=:entry",
            'data' => [ 'entry' => $value],
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
        $entryId = $request->variable('entry', 0);
        if (!$entryId) {
            return [];
        }
        $sqls[] = $this->entry($request);
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
}
