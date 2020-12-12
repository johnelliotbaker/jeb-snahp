<?php
namespace jeb\snahp\Apps\Xmas;

require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/BingoBoard.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/Scorers.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/utility.php';

class XmasHelper
{
    const ROWS = 3;
    const COLUMNS = 3;
    const POOL_SIZE = 13;

    protected $sauth;
    public function __construct(
        $cache,
        $sauth,
        $Board,
        $Tile,
        $Vote,
        $Config
    ) {
        $this->cache = $cache;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
        $this->Board = $Board;
        $this->Tile = $Tile;
        $this->Vote = $Vote;
        $this->Config = $Config;
        $this->period = null;
    }/*}}}*/

    public function test()/*{{{*/
    {
    }/*}}}*/

    public function simulateVoting($period=0)/*{{{*/
    {
        $startTime = microtime(true);
        $this->Vote->simulate($period);
        $elapsed = microtime(true) - $startTime;
        prn($elapsed);
    }/*}}}*/

    public function summary()/*{{{*/
    {
        $startTime = microtime(true);
        $votes = getVotes();
        $availableVotes = getAvailableVotes();
        $user = $this->sauth->userId;
        // TODO::
        // $user = rand(60, 6000);
        // $user = 1;
        $board = $this->Board->getObject('user=?', [$user]);
        if (!$board) {
            $data = [
                'user' => $user,
                'created' => time(),
            ];
            $board = $this->Board->create($data);
        }
        $score = $this->scoreUser($user);
        $totalPlayers = getBoardCount();
        $distribution = $this->score();
        $elapsed = microtime(true) - $startTime;
        krsort($distribution);
        $currentVote = $this->getCurrentUserVote($user);
        $period = $this->getPeriod();
        $data = [
            'period' => (int) $period,
            'votes' => $votes,
            'availableVotes' => array_values($availableVotes),
            'score' => (int) $score,
            'nextPeriodStart' => (int) $this->getNextPeriodStart(),
            'distribution' => $distribution,
            'totalPlayers' => (int) $totalPlayers,
            'currentVote' => (int) $currentVote,
            'elapsed' => $elapsed,
            'board' => [
                'tiles' => json_decode($board->tiles, true),
            ],
        ];
        return $data;
    }/*}}}*/

    public function getNextPeriodStart()/*{{{*/
    {
        $period = $this->getPeriod();
        $schedule = $this->getSchedule();
        [$start, $interval] = [$schedule['start'], $schedule['duration'] / $schedule['division']];
        return $start + $interval * ($period + 1);
    }/*}}}*/

    public function getCurrentUserVote($userId)/*{{{*/
    {
        $index = $this->getPeriod();
        $instance = $this->Vote->getObject('user=? AND period=?', [$userId, $index]);
        if ($instance) {
            return $instance->tile;
        }
    }/*}}}*/

    public function getMostVotedTile($period=null)/*{{{*/
    {
        return $this->Vote->getMostVotedTile($period);
    }/*}}}*/

    public function createBoard($userId)/*{{{*/
    {
        $this->Board->create(
            [
                'user' => $userId,
                'created' => time(),
                'score' => 0
            ]
        );
    }/*}}}*/

    public function getSchedule()/*{{{*/
    {
        if ($this->schedule === null) {
            $this->schedule = getXmasConfig('schedule', 0);
        }
        return $this->schedule;
    }/*}}}*/

    public function score()/*{{{*/
    {
        $cacheDuration = 20;
        $varname = 'snp_xmas_score_distribution';
        $distribution = $this->cache->get($varname);
        if (!$distribution) {
            $votes = getVotes();
            $boards = $this->Board->getQueryset();
            $bingoBoard = new BingoBoard($this::ROWS, $this::COLUMNS, $this::POOL_SIZE);
            $scorer = new ScoreRule75();
            $scorer->sequence = $votes;
            $distribution = [];
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
    }/*}}}*/

    public function scoreUser($userId)/*{{{*/
    {
        $votes = getVotes();
        $board = $this->Board->getObject('user=?', [$userId]);
        $bingoBoard = new BingoBoard($this::ROWS, $this::COLUMNS, $this::POOL_SIZE);
        $bingoBoard->tiles = $board->tiles;
        $scorer = new ScoreRule75();
        $scorer->sequence = $votes;
        return $scorer->score($bingoBoard);
    }/*}}}*/

    public function testBingo()/*{{{*/
    {
        [$rows, $columns] = [3, 3];
        $poolSize =  13; // $rows * $columns * 1.4
        $draws = 10;
        $simulations = 6000;
        $dummyBoard = new BingoBoard($rows, $cols, $poolSize);
        $simulator = new Simulator($dummyBoard, $draws);
        $sequence = $simulator->sequence();
        $scorer = new ScoreRule75();
        $res = [];
        foreach (range(0, $simulations) as $i) {
            $board = new BingoBoard($rows, $columns, $poolSize);
            $board->makeRandom();
            $board->markSequence($sequence);
            $score = $scorer->score($board);
            $res[] = $score;
        }

        $total = [];
        foreach ($res as $score) {
            $total[$score] += 1;
        }
        ksort($total);
        print_r($total);
    }/*}}}*/

    public function getPeriod()/*{{{*/
    {
        if ($this->period === null) {
            $this->period = getTimeIndexWithDefault();
        }
        return $this->period;
    }/*}}}*/

    public function resetTimer($mode=0)/*{{{*/
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
                'start' => $now + 3600,
                'duration' => 86400,
                'end' => $now + 3600 + 86400,
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
    }/*}}}*/
}


class Simulator /*{{{*/
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

}/*}}}*/


class AlreadyVotedError extends \jeb\snahp\core\errors\SnahpException/*{{{*/
{
    const MESSAGE_PREFIX = 'AlreadyVotedError';
}/*}}}*/

class BoardDoesNotExistError extends \jeb\snahp\core\errors\SnahpException/*{{{*/
{
    const MESSAGE_PREFIX = 'BoardDoesNotExistError';
}/*}}}*/
