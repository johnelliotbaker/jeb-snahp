<?php
namespace jeb\snahp\Apps\RequestManager;

class RequestManagerHelper
{
    protected $db;/*{{{*/
    protected $tbl;
    protected $def;
    public function __construct(
        $db,
        $tbl,
        $requestConfig
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->def = $requestConfig['def'];
    }/*}}}*/

    public function changeSolver($topicId, $solverId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $solverId = (int) $solverId;
        $rd = $this->getRequestData($topicId);
        if ((int) $rd['status'] === (int) $this->def['solve']) {
            // If already solved, reduce the previous solver's solved amount
            $prevSolverId = $rd['fulfiller_uid'];
        }
        $forcedSolverData = $this->makeForcedSolverData($solverId);
        $this->updateRequestData($topicId, $forcedSolverData);
        $this->incrementSolvedRequest($solverId);
        if ($prevSolverId) {
            $this->decrementSolvedRequest($prevSolverId);
        }
    }/*}}}*/

    public function makeForcedSolverData($userId)/*{{{*/
    {
        $userData = $this->getUserData($userId);
        if (!$userData) {
            throw new UserNotFoundError('Error Code: 7d56d9710f');
        }
        $time = time();
        return [
            'fulfiller_uid' => $userData['user_id'],
            'fulfiller_username' => $userData['username'],
            'fulfiller_colour' => $userData['user_colour'],
            'commit_time' => $time,
            'fulfilled_time' => $time,
            'solved_time' => $time,
            'status' => $this->def['solve']
        ];
    }/*}}}*/

    public function getRequestData($topicId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $sql = 'SELECT * FROM ' . $this->tbl['req'] . " WHERE tid=${topicId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function getUserData($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sql = 'SELECT user_id, user_colour, username, snp_req_n_solve FROM '.USERS_TABLE." WHERE user_id=${userId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function updateRequestData($topicId, $data)/*{{{*/
    {
        $topicId = (int) $topicId;
        $sql = 'UPDATE ' . $this->tbl['req'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE tid=${topicId}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function changeUserSolvedRequestCount($userId, $amount)/*{{{*/
    {
        $userId = (int) $userId;
        $amount = (int) $amount;
        $userData = $this->getUserData($userId);
        $prev = $userData['snp_req_n_solve'];
        $data = [
            'snp_req_n_solve' => $prev + $amount,
        ];
        $sql = 'UPDATE ' . USERS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id=${userId}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function incrementSolvedRequest($userId)/*{{{*/
    {
        $this->changeUserSolvedRequestCount($userId, 1);
    }/*}}}*/

    public function decrementSolvedRequest($userId)/*{{{*/
    {
        $this->changeUserSolvedRequestCount($userId, -1);
    }/*}}}*/
}

class UserNotFoundError extends \Exception
{
}
