<?php

function getXmasConfig($name, $cache=3600)
{
    global $db;
    $sql = "SELECT * FROM phpbb_snahp_xmas_config WHERE name='$name'";
    $result = $db->sql_query($sql, $cache);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    if (!$row) {
        throw new ConfigNotFoundError('Error Code: e976c14df8');
    }
    return json_decode($row['data'], true);
}

function setVotes($votes)
{
    global $db;
    $tbl = 'phpbb_snahp_xmas_vote';
    $votes = json_encode($votes);
    $sql = "UPDATE `phpbb_snahp_xmas_config` SET `data` = '{$votes}' WHERE (`name` = 'votes')";
    $db->sql_query($sql);
}

function getAvailableVotes($time=null)
{
    if ($time === null) {
        $time = time();
    }
    $b = getXmasConfig('board');
    $poolSize = $b['poolSize'];
    $votes = asSet(getVotes($time));
    return array_filter(
        range(1, $poolSize),
        function ($arg) use ($votes) {
            return !isset($votes[$arg]);
        }
    );
}

function clearVoteCache()
{
    global $db;
    $sql = "UPDATE `phpbb_snahp_xmas_config` SET `data` = '[]' WHERE (`name` = 'votes');";
    $db->sql_query($sql);
}

function getVoteDistribution($period=null)
{
    global $db;
    // TODO:: cache
    $cache = 0;
    if ($period === null) {
        $period = getTimeIndexWithDefault();
    }
    $tbl = 'phpbb_snahp_xmas_vote';
    $sql = "SELECT tile, count(*) as total FROM ${tbl} WHERE period=${period} GROUP BY tile;";
    $result = $db->sql_query($sql, $cache);
    $rowset = $db->sql_fetchrowset($result);
    $db->sql_freeresult($result);
    $res = [ 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0, 11=>0, 12=>0, 13=>0, ];
    if ($rowset) {
        foreach ($rowset as $row) {
            $res[$row['tile']] = $row['total'];
        }
    }
    return $res;
}

function getVotes($time=null)
{
    // return [1,2,3,4,5,6,7,8,9,10,11,12,13];
    global $db;
    if ($time === null) {
        $time = time();
    }
    $board = getXmasConfig('board', 0);
    $schedule = getXmasConfig('schedule', 0);
    $index = getTimeIndex(
        $time,
        $schedule['start'],
        $schedule['duration'],
        $schedule['division']
    );
    $votes = getXmasConfig('votes', 0);
    if ($index === count($votes)) {
        return $votes;
    }
    $tbl = 'phpbb_snahp_xmas_vote';
    $res = [];
    $pool = range(1, $board['poolSize']);
    shuffle($pool);
    for ($i = 0; $i < $index; $i++) {
        $sql = "SELECT tile, COUNT(*) as total FROM {$tbl} WHERE period=$i GROUP BY tile ORDER BY total DESC, tile DESC";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if ($row) {
            $value = (int) $row['tile'];
        } else {
            $diff = array_diff($pool, $res);
            $value = array_pop($diff);
        }
        $res[] = $value;
    }
    setVotes($res);
    return $res;
}

function getTimeIndexWithDefault()
{
    $schedule = getXmasConfig('schedule', 0);
    return getTimeIndex(
        time(),
        $schedule['start'],
        $schedule['duration'],
        $schedule['division']
    );
}

function getTimeIndex($time, $start, $duration, $division)
{
    $fromStart = $time - $start;
    if ($fromStart < 0) {
        return -1;
    }
    $interval = $duration / $division;
    return min((int) ($fromStart / $interval), $division);
}

class ConfigNotFoundError extends \jeb\snahp\core\errors\SnahpException
{
    const MESSAGE_PREFIX = 'ConfigNotFoundError';
}

function getBoardCount()
{
    global $db;
    $sql = 'SELECT count(*) as total FROM phpbb_snahp_xmas_board';
    $result = $db->sql_query($sql, 0);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    if (!$row) {
        return 0;
    }
    return $row['total'];
}
