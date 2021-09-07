<?php
namespace jeb\snahp\Apps\Throttle;

class DBEntryDoesNotExist extends \Exception
{
}

class Toplist
{
    public function __construct($request, $tbl, $paginator, $QuerySetFactory)/*{{{*/
    {
        $this->request = $request;
        $this->tbl = $tbl;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->paginator = $paginator;
    }/*}}}*/

    public function getToplist($order_by)/*{{{*/
    {
        $username = $this->request->variable('username', '');
        $sqlAry = [
            'SELECT' => 'b.username, b.user_colour, a.*',
            'FROM' => [$this->tbl['throttle'] => 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [USERS_TABLE => 'b'],
                    'ON' => 'a.user_id = b.user_id',
                ]
            ],
            'ORDER_BY' => "a.{$order_by} DESC"
        ];
        if ($username) {
            $sqlAry['WHERE'] = "b.username='$username'";
        }
        $qs = $this->QuerySetFactory->fromSqlArray($sqlAry);
        $qs = $this->paginator->paginateQueryset($qs, $this->request);
        $qs = $this->paginator->getPaginatedResult($qs);
        foreach ($qs['results'] as $k => &$v) {
            $v['data'] = unserialize($v['data']);
        }
        return $qs;
    }/*}}}*/
}

class ThrottleHelper
{
    public function __construct(/*{{{*/
        $db,
        $request,
        $config,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->config = $config;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;

        $this->col = $this->userVisitTimestampColumnName = 'snp_log_visit_timestamps';
    }/*}}}*//*}}}*/

    public function getToplist($orderBy)/*{{{*/
    {
        $tl = new Toplist($this->request, $this->tbl, $this->paginator, $this->QuerySetFactory);
        return $tl->getToplist($orderBy);
    }/*}}}*/

    public function logUserVisitStatistics($userId, $isFlooding)/*{{{*/
    {
        $stats = $this->getOrCreateVisitStatistics($userId);
        $extraData = new ExtraData($stats['data']);
        $nowMonth = (int) date('m');
        if ($extraData->getTrackedMonth() !== $nowMonth) {
            $extraData->startNewMonth($nowMonth, $stats['monthly_visit'], $stats['monthly_flood']);
            $stats['data'] = (string) $extraData;
            $stats['monthly_visit'] = 0;
            $stats['monthly_flood'] = 0;
        }

        $stats['total_visit'] += 1;
        $stats['monthly_visit'] += 1;
        if ($isFlooding) {
            $stats['total_flood'] += 1;
            $stats['monthly_flood'] += 1;
        }
        $this->updateUserVisitStatistics($userId, $stats);
    }/*}}}*/

    public function getOrCreateVisitStatistics($userId)/*{{{*/
    {
        try {
            $row = $this->getUserVisitStatistics($userId);
        }  catch (DBEntryDoesNotExist $e) {
            $extraData = (new ExtraData());
            $stats = ['user_id' => (int) $userId, 'data' => (string) $extraData];
            $this->createUserVisitStatistics($userId, $stats);
            $row = $this->getUserVisitStatistics($userId);
        }
        return $row;
    }/*}}}*/

    public function createUserVisitStatistics($userId, $data)/*{{{*/
    {
        $sql = 'INSERT INTO ' . $this->tbl['throttle'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    public function updateUserVisitStatistics($userId, $data)/*{{{*/
    {
        $ary = [
            'UPDATE',
            $this->tbl['throttle'],
            'SET',
            $this->db->sql_build_array('UPDATE', $data),
            "WHERE user_id=${userId}"
        ];
        $sql = implode(' ', $ary);
        $this->db->sql_query($sql);
    }/*}}}*/

    public function getUserVisitStatistics($userId)/*{{{*/
    {
        $sqlAry = [
            'SELECT'   => '*',
            'FROM'     => [$this->tbl['throttle'] => 'a'],
            'WHERE'    => "user_id={$userId}",
        ];
        $row = $this->QuerySetFactory->fromSqlArray($sqlAry)->slice(0, 1, false);
        if (!$row) {
            throw new DBEntryDoesNotExist("User does not exist. Error Code: 3b2b11fee9");
        }
        return $row;
    }/*}}}*/

    public function banUserUntil($userId, $duration)/*{{{*/
    {
        $data['ban_until'] = time() + $duration;
        $this->updateUserVisitStatistics($userId, $data);
    }/*}}}*/

    public function throttleUser($userId, $cfg)/*{{{*/
    {
        $isLogging = $cfg['enable_logging'];
        $isThrottling = $cfg['enable_throttle'];
        if ($isLogging) {
            $this->recordUserVisit($userId, $timestamps, $cfg['count']);
        }
        $timestamps = $this->getOrCreateVisitTimestamps($userId, $cfg['count']);
        $isFlooding = $this->isFlooding($timestamps, $cfg['interval']);
        if ($isLogging) {
            $this->logUserVisitStatistics($userId, $isFlooding);
        }
        if ($isFlooding && $isLogging) {
            $this->banUserUntil($userId, $cfg['ban_duration']);
        }
        if ($isThrottling && $banDuration=$this->getUserBanDuration($userId)) {
            if ($this->request->is_ajax()) {
                http_response_code(429);
                exit();
            }
            trigger_error("You cannot access the forum for ${banDuration} seconds. Error Code: e1003e6f6f");
        }
    }/*}}}*/

    public function getUserBanDuration($userId)/*{{{*/
    {
        $stats = $this->getOrCreateVisitStatistics($userId);
        return max(0, $stats['ban_until'] - time());
    }/*}}}*/

    public function getOrCreateVisitTimestamps($userId, $length)/*{{{*/
    {
        $ts = $this->getUserVisitTimestamps($userId);
        if (!$this->validateUserTimestamps($ts, $length)) {
            $this->resetTimestamps($userId, $length);
            $ts = $this->getUserVisitTimestamps($userId);
            if (!$this->validateUserTimestamps($ts, $length)) {
                throw new \Exception('Error Code: 2f9e4972be');
            }
        }
        return $ts;
    }/*}}}*/

    public function getUserVisitTimestamps($userId)/*{{{*/
    {
        $sql = "SELECT {$this->col} FROM " . USERS_TABLE . " WHERE user_id=${userId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            throw new \Exception("User does not exist. Error Code: c3f928c9ad");
        }
        return unserialize($row[$this->col]);
    }/*}}}*/

    public function validateUserTimestamps($timestamps, $length)/*{{{*/
    {
        if (!is_array($timestamps)) {
            return false;
        }
        if (count($timestamps) !== $length) {
            return false;
        }
        return true;
    }/*}}}*/

    public function resetTimestamps($userId, $length)/*{{{*/
    {
        $timestamps = array_fill(0, $length, 0);
        $this->updateUserTimestamps($userId, $timestamps);
    }/*}}}*/

    public function updateUserTimestamps($userId, $timestamps)/*{{{*/
    {
        $time = serialize($timestamps);
        $userId = (int) $userId;
        $sql = "UPDATE phpbb_users SET $this->col='${time}' WHERE user_id=${userId}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function recordUserVisit($userId, $timestamps, $count)/*{{{*/
    {
        if (!isset($timestamps)) {
            $timestamps = $this->getOrCreateVisitTimestamps($userId, $count);
        }
        $timestamps = array_merge(array_slice($timestamps, 1), [time()]);
        $this->updateUserTimestamps($userId, $timestamps);
    }/*}}}*/

    public function isFlooding($timestamps, $interval)/*{{{*/
    {
        $time = time();
        if ($time < (reset($timestamps) + $interval)) {
            return true;
        }
        return false;
    }/*}}}*/

    public function enableMaster()/*{{{*/
    {
        $this->config->set('snp_throttle_enable_master', 1);
    }/*}}}*/

    public function disableMaster()/*{{{*/
    {
        $this->config->set('snp_throttle_enable_master', 0);
    }/*}}}*/

    public function enableLogging()/*{{{*/
    {
        $this->config->set('snp_throttle_enable_logging', 1);
    }/*}}}*/

    public function disableLogging()/*{{{*/
    {
        $this->config->set('snp_throttle_enable_logging', 0);
    }/*}}}*/

    public function enableThrottle()/*{{{*/
    {
        $this->config->set('snp_throttle_enable_throttle', 1);
    }/*}}}*/

    public function disableThrottle()/*{{{*/
    {
        $this->config->set('snp_throttle_enable_throttle', 0);
    }/*}}}*/
}


class ExtraData
{
    public function __construct($serializedData=null)/*{{{*/
    {
        if ($serializedData === null) {
            $this->initializeData();
        } else {
            $this->data = unserialize($serializedData);
        }
        $this->currentMonth = (int) date('m');
    }/*}}}*/

    public function __toString()/*{{{*/
    {
        return serialize($this->data);
    }/*}}}*/

    public function initializeData()/*{{{*/
    {
        $this->data = [
            'tracking' => [
                'month' => null,
            ],
            'monthly_stats' => [
            ],
        ];
    }/*}}}*/

    public function setCurrentMonth($month)/*{{{*/
    {
        $this->currentMonth = (int) $month;
    }/*}}}*/

    public function getTrackedMonth()/*{{{*/
    {
        return $this->data['tracking']['month'];
    }/*}}}*/

    public function startNewMonth($month, $visit=0, $flood=0)/*{{{*/
    {
        $previousMonth = $this->data['tracking']['month'];
        if ($previousMonth !== null) {
            $this->setMonthlyVisitData($previousMonth, $visit);
            $this->setMonthlyFloodData($previousMonth, $flood);
        }
        $this->setTrackedMonthData($month);
    }/*}}}*/

    public function setTrackedMonthData($month)/*{{{*/
    {
        $this->data['tracking']['month'] = (int) $month;
    }/*}}}*/

    public function setMonthlyFloodData($month, $val)/*{{{*/
    {
        $this->data['monthly_stats'][$month]['flood'] = (int) $val;
    }/*}}}*/

    public function setMonthlyVisitData($month, $val)/*{{{*/
    {
        $this->data['monthly_stats'][$month]['visit'] = (int) $val;
    }/*}}}*/
}
