<?php

namespace jeb\snahp\Apps\Wiki\Models;

require_once 'ext/jeb/snahp/core/Rest/Model.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\CommaSeparatedPositiveIntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class Entry extends Model
{
    const TABLE_NAME = 'phpbb_snahp_wiki_entry';
    const HISTORY_TABLE_NAME = 'phpbb_snahp_wiki_entry_history';
    const FOREIGN_NAME = 'phpbb_snahp_wiki_group';

    protected $fields;
    protected $requiredFields = ['author', 'subject', 'text'];
    protected $foreignNameParam = 'group';

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'author' => new IntegerField(),
            'subject' => new StringField(),
            'text' => new StringField(),
        ];
        $this->query = [
            'statement' => '1=1',
            'data' => [],
        ];
    }

    public function getDiff($instance)
    {
        $orig = $instance->getMeta('sys.orig');
        $diffText = xdiff_string_diff($orig['text'], $instance->text);
        $diffSubject = $orig['subject'] === $instance->subject ? null : $orig['subject'];
        if ($diffText || $diffSubject) {
            return ['text' => $diffText, 'subject' => $diffSubject];
        }
        return null;
    }

    public function performPreUpdate($instance)/*{{{*/
    {
        # in-place for performance
        global $user;
        $instance->author = $user->data['user_id'];

        if ($diff = $this->getDiff($instance)) {
            $history = R::xdispense($this::HISTORY_TABLE_NAME);
            $history->patch = $diff['text'];
            $history->author = $instance->author;
            $history->subject = $diff['subject'];
            R::store($history);

            $ownlistname = 'own' . ucfirst($this::TABLE_NAME) . 'List';
            $instance->$ownlistname[] = $history;
        }
    }/*}}}*/

    public function foreign($request)/*{{{*/
    {
        $value = $request->variable($this->foreignNameParam, 0);
        if ($value < 1) {
            return [
                'statement' => '',
                'data' => [],
            ];
        }
        $foreignPkName = $this::FOREIGN_NAME . '_id';
        return [
            'statement' => "AND ${foreignPkName}=:foreign",
            'data' => [ 'foreign' => $value],
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

    public function filter($request)/*{{{*/
    {
        $sqls[] = $this->foreign($request);
        foreach ($sqls as $sql) {
            $this->mergeQuery($sql);
        }
        $sortSnippet = $this->sort($request);
        $result = R::find($this::TABLE_NAME, $this->query['statement'], $this->query['data'], $sortSnippet);
        return $result;
    }/*}}}*/

    public function performPreCreate($instance)/*{{{*/
    {
        global $user;
        $instance->author = $user->data['user_id'];
    }/*}}}*/
}
