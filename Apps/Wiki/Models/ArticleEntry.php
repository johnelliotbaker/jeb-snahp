<?php
namespace jeb\snahp\Apps\Wiki\Models;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\BoolField;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class ArticleEntry extends Model
{
    const TABLE_NAME = "phpbb_snahp_wiki_article_entry";
    const HISTORY_TABLE_NAME = "phpbb_snahp_wiki_article_entry_history";
    const FOREIGN_NAME = "phpbb_snahp_wiki_article_group";

    protected $fields;
    protected $requiredFields = [
        "author",
        "subject",
        "text",
        "hidden",
        "priority",
        "hash",
        "created_time",
        "modified_time",
    ];
    protected $foreignNameParam = "group";

    public function __construct($History)
    {
        $this->history = $History;
        parent::__construct();
        $this->fields = [
            "author" => new IntegerField(["default" => $this->userId]),
            "subject" => new StringField(),
            "text" => new StringField(),
            "created_time" => new IntegerField(["default" => time()]),
            "modified_time" => new IntegerField(["default" => 0]),
            "hidden" => new BoolField(["default" => true]),
            "priority" => new IntegerField(["default" => 500]),
            "hash" => new StringField(["default" => uuid4()]),
        ];
    }

    public function getDiff($instance)
    {
        return null;
        $orig = $instance->getMeta("sys.orig");
        $diffText = xdiff_string_diff($orig["text"], $instance->text);
        $diffSubject =
            $orig["subject"] === $instance->subject ? null : $orig["subject"];
        if ($diffText || $diffSubject) {
            return ["text" => $diffText, "subject" => $diffSubject];
        }
        return null;
    }

    public function performPreUpdate($instance)
    {
        $instance->author = $this->userId;
        if ($diff = $this->getDiff($instance)) {
            $modifiedTime = time();
            $data = [
                "author" => $instance->author,
                "text" => $diff["text"],
                "subject" => $diff["subject"],
                "parenthash" => $instance->hash,
                "modified_time" => $modifiedTime,
            ];
            $history = $this->history->create($data);
            $ownlistname = "own" . ucfirst($this->history::TABLE_NAME) . "List";
            $instance->$ownlistname[] = $history;
            $instance->hash = uuid4();
            $instance->modifiedTime = $modifiedTime;
        }
    }

    public function foreign($request)
    {
        $value = $request->variable($this->foreignNameParam, 0);
        if ($value < 1) {
            return [
                "statement" => "",
                "data" => [],
            ];
        }
        $foreignPkName = $this::FOREIGN_NAME . "_id";
        return [
            "statement" => "AND ${foreignPkName}=:foreign",
            "data" => ["foreign" => $value],
        ];
    }

    public function sort($request)
    {
        $allowedSortKeys = ["id"];
        $sortBy = $request->variable("sortBy", "");
        if (!in_array($sortBy, $allowedSortKeys)) {
            return "";
        }
        $sortOrder = $request->variable("sortOrder", "DESC");
        $sortOrder = $sortOrder === "DESC" ? "DESC" : "ASC";
        if ($sortBy === "") {
            return "";
        }
        return "ORDER BY {$sortBy} {$sortOrder}";
    }

    public function mergeQuery($newQuery)
    {
        if (!$newQuery["statement"]) {
            return $this->query;
        }
        $this->query["statement"] .= " " . $newQuery["statement"];
        $this->query["data"] = array_merge(
            $this->query["data"],
            $newQuery["data"]
        );
        return $this->query;
    }

    public function filter($request)
    {
        $sqls[] = $this->foreign($request);
        foreach ($sqls as $sql) {
            $this->mergeQuery($sql);
        }
        $sortSnippet = $this->sort($request);
        $result = R::find(
            $this::TABLE_NAME,
            $this->query["statement"],
            $this->query["data"],
            $sortSnippet
        );
        return $result;
    }

    public function performPreCreate($data)
    {
        $data["author"] = $this->userId;
        return $data;
    }

    public function getObjectFromSubject($subject)
    {
        return R::findOne($this::TABLE_NAME, "subject=?", [$subject]);
    }
}
