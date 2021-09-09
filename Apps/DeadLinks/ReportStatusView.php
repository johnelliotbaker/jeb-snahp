<?php

namespace jeb\snahp\Apps\DeadLinks;

use Symfony\Component\HttpFoundation\JsonResponse;
use \R as R;

class ReportStatusView
{
    protected $db;
    protected $request;
    protected $sauth;
    public function __construct($db, $request, $sauth, $model)
    {
        $this->db = $db;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->Entry = $model;
        $this->sauth->reject_new_users("Error Code: aa223b1e57");
    }

    public function view($topicId)
    {
        $topicId = (int) $topicId;
        $Entry = $this->Entry;
        $statement = "topic=? AND type=? AND status=? ORDER by id DESC";
        $values = [$topicId, "Report", "Open"];
        $report = $Entry->getObject($statement, $values);
        if (!$report) {
            throw new \Exception("Resource not found. Error Code: a7a5fd0a9b");
        }
        $report->reporter = $Entry->getUserInfo($report->user);
        return new JsonResponse($report);
    }
}
