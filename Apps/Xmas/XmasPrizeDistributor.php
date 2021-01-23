<?php
namespace jeb\snahp\Apps\Xmas;

require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/BingoBoard.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/Scorers.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/utility.php';
require_once '/var/www/forum/ext/jeb/snahp/core/bank/bank_helper.php';
// /var/www/forum/ext/jeb/snahp/core/bank/user_account.php // Bank User Account




const QUEST_DEFINITIONS = [
    'vanguards' => [
        'prize' => 25000,
        'name' => 'Vanguard',
        'users' => [2, 22028, 154243, 4638, 153628, 161554, 144708, 8603, 97117, 7241, 16256],
        'notificationItemID' => 1,
    ],
    'chef' => [
        'prize' => 30000,
        'name' => 'Master Chef',
        'users' => [2, 36325, 13471, 70491, 9058, 154129, 44304, 46557, 64172, 154243, 27331],
        'notificationItemID' => 2,
    ],
    'planner' => [
        'prize' => 30000,
        'name' => 'Christmas Planner',
        'users' => [2, 74687, 35138, 33514, 37930, 40349, 122714, 138434, 130832, 4290, 52378],
        'notificationItemID' => 3,
    ],
    'white' => [
        'prize' => 10000,
        'name' => 'White Christmas',
        'users' => [2, 154243, 61454, 27331, 7241, 86967, 51956, 4240, 122796, 15498, 30575],
        'notificationItemID' => 4,
    ],
    'dancers' => [
        'prize' => 30000,
        'name' => 'Christmas Line Dancers',
        'users' => [2, 156196, 28804, 20405, 20689, 4764],
        'notificationItemID' => 5,
    ],
];


class XmasPrizeDistributor
{
    const SCORE_TO_PRIZE = [
        0 => 100000,
        1 => 10000,
        2 => 20000,
        3 => 30000,
        4 => 40000,
        5 => 75000,
        6 => 100000,
        8 => 100000,
    ];

    public function __construct($Notification, $Board, $BankUserAccount)
    {
        $this->Notification = $Notification;
        $this->Board = $Board;
        $this->BankUserAccount = $BankUserAccount;
    }


    public function sendNotification($userId, $description='', $id=0)
    {
        $notificationItemID = $id;
        $notification_data = [
            'recipient_id' => $userId,
            'message'      => 'my good message',
            'title'       => '2020 Christmas Event',
            'description' => $description,
            'link'        => '/app.php/snahp/economy/dashboard/overview/',
            'item_id'     => $notificationItemID,
            'type'        => '2020 Christmas Event',
        ];
        $this->Notification->add_notifications('jeb.snahp.notification.type.simple', $notification_data);
        $this->Notification->mark_notifications(
            'jeb.snahp.notification.type.simple',
            $notificationItemID,
            $userId,
            $time = time() * 2,
            $mark_read = false
        );
    }

    public function processQuestPrize($data)
    {
        $users = $data['users'];
        $prize = $data['prize'];
        $name = $data['name'];
        $notificationItemID = $data['notificationItemID'];
        $prizeString = number_format($prize);
        $message =  "Received \${$prizeString} for 2020 Christmas Event ({$name})";
        foreach ($users as $userId) {
            $this->sendNotification($userId, $message, $id = $notificationItemID);
            $this->BankUserAccount->create_transaction_and_deposit($prize, $userId, -1, $message);
        }
    }

    public function processPrize($userId, $score)
    {
        $prize = self::SCORE_TO_PRIZE[$score];
        $prizeString = number_format($prize);
        $this->BankUserAccount->create_transaction_and_deposit(
            $prize,
            $userId,
            -1,
            "Received \${$prizeString} for 2020 Christmas Event (Score of {$score})"
        );
        $invite = $score === 0 || $score === 8 ? 1 : 0;
        if ($invite) {
            $message = "Received \${$prizeString} and 1 invitation point for scoring ${score} points";
            $this->BankUserAccount->giveInvitationPoints(
                $amount = 1,
                $userId,
                $type = 'giveaway',
                $comment = "Received 1 invitation point for 2020 Christmas Event Jackpot (Score of ${score})"
            );
        } else {
            $message = "Received \${$prizeString} for scoring ${score} points";
        }
        $this->sendNotification($userId, $message, $id = 0);
        return [$prize, $invite];
    }

    public function distribute($start)
    {
        $limit = 1000;
        $start = $start * $limit;
        $end = $start + $limit - 1;
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        // Normal Prizes
        $startTime = microtime(true);
        $votes = getVotes();
        $boards = $this->Board->getQueryset();
        $boardConfig = getXmasConfig('board');
        $bingoBoard = new BingoBoard($boardConfig['rows'], $boardConfig['columns'], $boardConfig['poolSize']);
        $scorer = new ScoreRule75();
        $scorer->sequence = $votes;
        $stats = ['prize'=>0, 'invite'=>0];
        foreach ($boards as $index => $board) {
            if ($index < $start) {
                continue;
            }
            if ($index > $end) {
                break;
            }
            $userId = (int) $board->user;
            $bingoBoard->tiles = $board->tiles;
            $score = (int) $scorer->score($bingoBoard);
            [$prize, $invite] = $this->processPrize($userId, $score);
            $stats['prize'] += $prize;
            $stats['invite'] += $invite;
            if ($index % 100 === 0) {
                $data = ['index' => $index, ];
                sendMessage($data);
            }
        }
        print_r($stats);
        $elapsed = microtime(true) - $startTime;
        print_r("Took ${elapsed} seconds.<br/>");
    }

    public function distributeQuestPrizes()
    {
        print_r('Distributing Prizes for Quest Completion.');
        $startTime = microtime(true);
        foreach (QUEST_DEFINITIONS as $quest) {
            $this->processQuestPrize($quest);
        }
        $elapsed = microtime(true) - $startTime;
        print_r("Took ${elapsed} seconds.<br/>");
    }

}

function markUnread($userId, $itemID)
{
    $this->Notification->mark_notifications('jeb.snahp.notification.type.simple', $itemID, $userId, $time = true, $mark_read = false);
}

function sendMessage($data)
{
    echo "data: " . json_encode($data) . PHP_EOL;
    ob_flush();
    flush();
}
