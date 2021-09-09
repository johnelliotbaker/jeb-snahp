<?php
namespace jeb\snahp\Apps\ReportTool;

class ReportToolHelper
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getReportedTime($postId)
    {
        $sql =
            "SELECT report_time FROM " .
            REPORTS_TABLE .
            " WHERE post_id=${postId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row["report_time"] ?? null;
    }
}
