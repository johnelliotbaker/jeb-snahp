<?php
namespace jeb\snahp\Apps\Database;

class DatabaseHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;

    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function getTableNames()
    {
        $names = $this->exec(
            "SELECT table_name FROM information_schema.tables WHERE table_name LIKE 'phpbb_%'",
            900
        );
        return array_column($names, "table_name");
    }

    public function getColumnNames($table)
    {
        return array_keys(\R::inspect($table));
    }

    public function select($sqlArray, $limit = 100)
    {
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query_limit($sql, $limit);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $sql];
    }

    public function exec($sql, $limit = 100)
    {
        $result = $this->db->sql_query_limit($sql, $limit);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function getFormData($request, $default)
    {
        $data = [];
        $submit = $request->is_set_post("submit");
        $map = [
            "table" => "",
            "parse" => "off",
            "fields" => "*",
            "where" => "",
            "orderBy" => "",
            "limit" => 100,
        ];
        if ($submit) {
            if (!check_form_key("jeb_snp")) {
                trigger_error("FORM_INVALID");
            }
            $data["statement"] = htmlspecialchars_decode(
                $request->variable("statement", "")
            );

            $data["submit"] = $submit;
            foreach ($map as $k => $v) {
                $data[$k] = htmlspecialchars_decode($request->variable($k, $v));
            }
        }
        return array_merge($default, $data);
    }

    public function processText($table, $rowset)
    {
        $textProcessor = function ($data) {
            return generate_text_for_display($data, "", "", 0);
        };
        $map = [
            "phpbb_posts" => [["post_text", $textProcessor]],
        ];
        $processors = $map[$table] ?? [];
        foreach ($processors as $processor) {
            [$column, $processor] = $processor;
            foreach ($rowset as &$row) {
                $row[$column] = $processor($row[$column]);
            }
        }
        return $rowset;
    }
}
