<?php
namespace jeb\snahp\controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

class acp_thanks extends base
{
    public function __construct()
    {
    }

    public function handle($mode)
    {
        switch ($mode) {
            case "resync_all":
                $cfg = [];
                return $this->handle_resync_all($cfg);
            case "do_resync_all":
                $cfg = [];
                return $this->do_resync_all($cfg);
            default:
                break;
        }
        trigger_error("You must specify valid mode.");
    }

    public function send_message($data)
    {
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    public function do_resync_all($cfg)
    {
        // Set sql limit
        $limit = 100;
        $this->reject_non_admin();
        // Set header for json update
        header("Content-Type: text/event-stream");
        header("Cache-Control: no-cache");
        $tbl = $this->container->getParameter("jeb.snahp.tables");
        // Clear all thanks count from user
        // $sql = 'UPDATE ' . USERS_TABLE . '
        //    SET snp_thanks_n_given=0, snp_thanks_n_received=0';
        // $this->db->sql_query($sql);
        // Get total for transaction given
        $sql =
            "SELECT COUNT(DISTINCT user_id) as total FROM " .
            $tbl["thanks"] .
            " ORDER BY user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row["total"];
        $start = 0;
        while ($start < $total) {
            $this->db->sql_return_on_error(true);
            try {
                $this->db->sql_transaction("begin");
                $sql =
                    "SELECT DISTINCT user_id FROM " .
                    $tbl["thanks"] .
                    " ORDER BY user_id";
                $result = $this->db->sql_query_limit($sql, $limit, $start);
                $rowset = $this->db->sql_fetchrowset($result);
                foreach ($rowset as $row) {
                    $user_id = $row["user_id"];
                    $sql =
                        "SELECT COUNT(*) as total FROM " .
                        $tbl["thanks"] .
                        " WHERE user_id=" .
                        $user_id;
                    $result0 = $this->db->sql_query($sql);
                    $row0 = $this->db->sql_fetchrow($result0);
                    $this->db->sql_freeresult($result0);
                    $n_given = $row0["total"];
                    $sql =
                        "UPDATE " .
                        USERS_TABLE .
                        " SET snp_thanks_n_given=" .
                        $n_given .
                        " WHERE user_id=" .
                        $user_id;
                    $this->db->sql_query($sql);
                }
                $this->db->sql_freeresult($result);
                if ($this->db->get_sql_error_triggered()) {
                    throw new \Exception();
                }
                $this->db->sql_transaction("commit");
            } catch (\Exception $e) {
                $this->db->sql_transaction("rollback");
                $this->db->sql_transaction("commit");
                $error_query = $this->db->get_sql_error_sql();
                $error_msg = implode(
                    "<br>",
                    $this->db->get_sql_error_returned()
                );
                $err = $error_query . "<br>";
                $err .= $error_msg;
                $data = [
                    "status" => "ERROR",
                    "i" => $total,
                    "n" => $total,
                    "message" => "$total of $total",
                    "error_message" => $err,
                    "sqlmsg" => $sql,
                ];
                $this->send_message($data);
                $this->db->sql_return_on_error(false);
                trigger_error($err);
            }
            $this->db->sql_return_on_error(false);
            $data = [
                "status" => "PROGRESS",
                "i" => $start,
                "n" => $total,
                "message" => "$start of $total",
                "error_message" => "No Errors",
                "sqlmsg" => $sql,
            ];
            $this->send_message($data);
            $start += $limit;
        }
        $data = [
            "status" => "PROGRESS",
            "i" => $total,
            "n" => $total,
            "message" => "$total of $total",
            "error_message" => "No Errors",
            "sqlmsg" => $sql,
        ];
        $this->send_message($data);
        // Get total for transaction received
        $sql =
            "SELECT COUNT(DISTINCT poster_id) as total FROM " .
            $tbl["thanks"] .
            " ORDER BY poster_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row["total"];
        // Process thanks received
        $start = 0;
        while ($start < $total) {
            $this->db->sql_return_on_error(true);
            try {
                $this->db->sql_transaction("begin");
                $sql =
                    "SELECT DISTINCT poster_id FROM " .
                    $tbl["thanks"] .
                    " ORDER BY poster_id";
                $result = $this->db->sql_query_limit($sql, $limit, $start);
                $rowset = $this->db->sql_fetchrowset($result);
                foreach ($rowset as $row) {
                    $poster_id = $row["poster_id"];
                    $sql =
                        "SELECT COUNT(*) as total FROM " .
                        $tbl["thanks"] .
                        " WHERE poster_id=" .
                        $poster_id;
                    $result0 = $this->db->sql_query($sql);
                    $row0 = $this->db->sql_fetchrow($result0);
                    $this->db->sql_freeresult($result0);
                    $n_received = $row0["total"];
                    $sql =
                        "UPDATE " .
                        USERS_TABLE .
                        " SET snp_thanks_n_received=" .
                        $n_received .
                        " WHERE user_id=" .
                        $poster_id;
                    $this->db->sql_query($sql);
                }
                $this->db->sql_freeresult($result);
                if ($this->db->get_sql_error_triggered()) {
                    throw new \Exception();
                }
                $this->db->sql_transaction("commit");
            } catch (\Exception $e) {
                $this->db->sql_transaction("rollback");
                $this->db->sql_transaction("commit");
                $error_query = $this->db->get_sql_error_sql();
                $error_msg = implode(
                    "<br>",
                    $this->db->get_sql_error_returned()
                );
                $err = $error_query . "<br>";
                $err .= $error_msg;
                $data = [
                    "status" => "ERROR",
                    "i" => $start,
                    "n" => $total,
                    "message" => "$start of $total",
                    "error_message" => $err,
                    "sqlmsg" => $sql,
                ];
                $this->send_message($data);
                $this->db->sql_return_on_error(false);
                trigger_error($err);
            }
            $this->db->sql_return_on_error(false);
            $data = [
                "status" => "PROGRESS",
                "i" => $start,
                "n" => $total,
                "message" => "$start of $total",
                "error_message" => "No Errors",
                "sqlmsg" => $sql,
            ];
            $this->send_message($data);
            $start += $limit;
        }
        $data = [
            "status" => "PROGRESS",
            "i" => $total,
            "n" => $total,
            "message" => "$total of $total",
            "error_message" => "No Errors",
            "sqlmsg" => $sql,
        ];
        $this->send_message($data);
        $js = new JsonResponse();
        return $js;
    }

    public function handle_resync_all()
    {
        $this->reject_non_admin();
        return $this->helper->render("@jeb_snahp/acp_thanks/resync_all.html");
    }
}
