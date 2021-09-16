<?php
namespace jeb\snahp\Apps\Database;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class DatabaseController
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $phpHelper;
    protected $tbl;
    protected $sauth;
    protected $helper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $phpHelper,
        $tbl,
        $sauth,
        $helper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->phpHelper = $phpHelper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        if (!$this->sauth->is_only_dev()) {
            throwHttpException(403, "Error Code: 5db658a7a1");
        }
    }

    public function view()
    {
        $cfg["tpl_name"] = "@jeb_snahp/database/base.html";
        $cfg["title"] = "Database Manager";
        $startTime = microtime(true);
        add_form_key("jeb_snp");
        $defaultFormData = ["fields" => "*", "limit" => 100];
        $formData = $this->helper->getFormData(
            $this->request,
            $defaultFormData
        );
        [
            "table" => $table,
            "parse" => $parse,
            "fields" => $fields,
            "where" => $where,
            "orderBy" => $orderBy,
            "limit" => $limit,
            "submit" => $submit,
        ] = $formData;
        if ($submit) {
            $columns =
                $fields === "*"
                    ? $this->helper->getColumnNames($table)
                    : explode(",", $fields);
            $sqlArray = [
                "SELECT" => $fields,
                "FROM" => [$table => "a"],
                "WHERE" => $where,
                "ORDER_BY" => $orderBy,
            ];
            [$rowset, $sql] = $this->helper->select($sqlArray, $limit);
            $rowset =
                $parse === "on"
                    ? $this->helper->processText($table, $rowset)
                    : $rowset;
            $elapsed = microtime(true) - $startTime;
        }
        $tableNames = $this->helper->getTableNames();
        $this->template->assign_vars([
            "COLUMNS" => $columns,
            "ROWSET" => $rowset,
            "PROCESS_TIME" => $elapsed,
            "STATEMENT" => $sql,
            "TABLE" => $table,
            "TABLE_NAMES" => $tableNames,
            "WHERE" => $where,
            "ORDER_BY" => $orderBy,
            "PARSE" => $parse,
            "LIMIT" => $limit,
            "FIELDS" => $fields,
        ]);
        return $this->phpHelper->render($cfg["tpl_name"], $cfg["title"]);
    }
}
