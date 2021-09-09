<?php
namespace jeb\snahp\Apps\Xmas;

require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/BingoBoard.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/Scorers.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/utility.php';

class XmasHelper
{
    protected $sauth;
    public function __construct(
        $cache,
        $sauth,
        $Board,
        $Vote,
        $Config
    ) {
        $this->cache = $cache;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
        $this->Board = $Board;
        $this->Vote = $Vote;
        $this->Config = $Config;
        $this->period = null;
        $this->boardConfig = null;
    }

    public function simulateVoting($period=0)
    {
        $startTime = microtime(true);
        $this->Vote->simulate($period);
        $elapsed = microtime(true) - $startTime;
        prn($elapsed);
    }

    public function summary()
    {
        $startTime = microtime(true);
        $votes = getVotes();
        $availableVotes = getAvailableVotes();
        $user = $this->sauth->userId;
        $board = $this->Board->getObject('user=?', [$user]);
        if (!$board) {
            $data = [
                'user' => $user,
                'created' => time(),
            ];
            $board = $this->Board->create($data);
        }
        $period = $this->getPeriod();
        $voteDistribution = getVoteDistribution($period);
        $score = $this->scoreUser($user);
        $totalPlayers = getBoardCount();
        $distribution = $this->getScoreDistribution();
        krsort($distribution);
        $currentVote = $this->getCurrentUserVote($user);
        $nextPeriodStart = (int) $this->getNextPeriodStart();
        $timeToNext = $nextPeriodStart - time();
        $elapsed = microtime(true) - $startTime;
        $data = [
            'period' => (int) $period,
            'votes' => $votes,
            'availableVotes' => array_values($availableVotes),
            'score' => (int) $score,
            'voteDistribution' => $voteDistribution,
            'nextPeriodStart' => $nextPeriodStart,
            'timeToNext' => $timeToNext,
            'distribution' => $distribution,
            'totalPlayers' => (int) $totalPlayers,
            'currentVote' => (int) $currentVote,
            'elapsed' => $elapsed,
            'board' => [
                'tiles' => json_decode($board->tiles, true),
            ],
        ];
        return $data;
    }

    public function getNextPeriodStart()
    {
        $period = $this->getPeriod();
        $schedule = $this->getSchedule();
        [$start, $interval] = [$schedule['start'], $schedule['duration'] / $schedule['division']];
        return $start + $interval * ($period + 1);
    }

    public function getCurrentUserVote($userId)
    {
        $index = $this->getPeriod();
        $instance = $this->Vote->getObject('user=? AND period=?', [$userId, $index]);
        if ($instance) {
            return $instance->tile;
        }
    }

    public function createBoard($userId)
    {
        $this->Board->create(
            [
                'user' => $userId,
                'created' => time(),
            ]
        );
    }

    public function getSchedule()
    {
        if ($this->schedule === null) {
            $this->schedule = getXmasConfig('schedule', 0);
        }
        return $this->schedule;
    }

    public function getScoreDistribution()
    {
        // TODO::
        $cacheDuration = 0;
        $varname = 'snp_xmas_score_distribution';
        $distribution = $this->cache->get($varname);
        if (!$distribution) {
            $distribution = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 8=>0];
            $votes = getVotes();
            $boards = $this->Board->getQueryset();
            $boardConfig = $this->getBoardConfig();
            $bingoBoard = new BingoBoard($boardConfig['rows'], $boardConfig['columns'], $boardConfig['poolSize']);
            $scorer = new ScoreRule75();
            $scorer->sequence = $votes;
            foreach ($boards as $board) {
                $bingoBoard->tiles = $board->tiles;
                $score = $scorer->score($bingoBoard);
                if (isset($distribution[$score])) {
                    $distribution[$score] += 1;
                } else {
                    $distribution[$score] = 1;
                }
            }
            ksort($distribution);
            $distribution = json_encode($distribution);
            $this->cache->put($varname, $distribution, $cacheDuration);
            $this->cache->save();
        }
        return json_decode($distribution, true);
    }

    public function scoreUser($userId)
    {
        $votes = getVotes();
        $board = $this->Board->getObject('user=?', [$userId]);
        $boardConfig = $this->getBoardConfig();
        $bingoBoard = new BingoBoard($boardConfig['rows'], $boardConfig['columns'], $boardConfig['poolSize']);
        $bingoBoard->tiles = $board->tiles;
        $scorer = new ScoreRule75();
        $scorer->sequence = $votes;
        return $scorer->score($bingoBoard);
    }

    public function getPeriod()
    {
        if ($this->period === null) {
            $this->period = getTimeIndexWithDefault();
        }
        return $this->period;
    }

    public function getBoardConfig()
    {
        if ($this->boardConfig === null) {
            $this->boardConfig = getXmasConfig('board');
        }
        return $this->boardConfig;
    }

    public function resetTimer($mode=0)
    {
        $now = time();
        $cfg = [
            // Real event
            0 => [
                'start' => 1607990400,
                'duration' => 864000,
                'end' => 1608854400,
                'division' => 10,
            ],
            // Very Short for checking scoring
            1 => [
                'start' => $now,
                'duration' => 2,
                'end' => $now + 2,
                'division' => 10,
            ],
            // Short for checking progression
            2 => [
                'start' => $now + 10,
                'duration' => 100,
                'end' => $now + 10 + 100,
                'division' => 10,
            ],
            // Short for checking progression
            3 => [
                'start' => $now,
                'duration' => 900,
                'end' => $now + 900,
                'division' => 10,
            ],
            // Beta Tester Run
            4 => [
                'start' => $now + 3600,
                'duration' => 86400,
                'end' => $now + 3600 + 86400,
                'division' => 10,
            ],
            // Beta Tester Run
            5 => [
                'start' => $now,
                'duration' => 864000,
                'end' => $now + 864000,
                'division' => 10,
            ],
        ];
        $Config = $this->Config;
        $instance = $Config->getObject('name=?', ['schedule']);
        $data = $cfg[(int) $mode];
        $Config->update($instance, ['data' => json_encode($data)]);
        $instance = $Config->getObject('name=?', ['votes']);
        $Config->update($instance, ['data' => json_encode([])]);
        $this->Vote->wipe();
        return $data;
    }
}


class Simulator
{
    public function __construct($board, $draws)
    {
        $this->board = $board;
        $this->draws = $draws;
    }

    public function sequence()
    {
        $pool = (array) $this->board->pool;
        return array_slice($pool, 0, $this->draws);
    }
}


class AlreadyVotedError extends \jeb\snahp\core\errors\SnahpException
{
    const MESSAGE_PREFIX = 'AlreadyVotedError';
}

class BoardDoesNotExistError extends \jeb\snahp\core\errors\SnahpException
{
    const MESSAGE_PREFIX = 'BoardDoesNotExistError';
}
