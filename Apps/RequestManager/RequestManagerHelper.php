<?php
namespace jeb\snahp\Apps\RequestManager;

class RequestManagerHelper
{
    public function __construct(
        $db,
        $Request,
        $Topic,
        $User,
        $tbl,
        $requestConfig
    ) {
        $this->db = $db;
        $this->Topic = $Topic;
        $this->Request = $Request;
        $this->User = $User;
        $this->tbl = $tbl;
        $this->def = $requestConfig['def'];
    }

    public function changeSolver($topicId, $solverId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $solverId = (int) $solverId;
        $rd = $this->Request->getWithTopicId($topicId);
        if ((int) $rd['status'] === (int) $this->def['solve']) {
            // If already solved, reduce the previous solver's solved amount
            $prevSolverId = $rd['fulfiller_uid'];
        }
        $forcedSolverData = $this->makeForcedSolverData($solverId);
        $this->Request->updateWithTopicId($topicId, $forcedSolverData);
        $this->incrementSolvedRequest($solverId);
        if ($prevSolverId) {
            $this->decrementSolvedRequest($prevSolverId);
        } else {
            // WARNING changeSolver will not properly give back the request slot.
            // Temporary fix: just reset the request user.
            global $phpbb_container;
            $acpReqs = $phpbb_container->get('jeb.snahp.acp_reqs');
            $username = $rd['requester_username'];
            $acpReqs->handle_reset_user($username);
        }
        $this->setTopicRequestTag($topicId, 'solved');
    }/*}}}*/

    public function setTopicRequestTag($topicId, $tagName)/*{{{*/
    {
        $topicId = (int) $topicId;
        $topicData = $this->Topic->get($topicId);
        $title = $topicData['topic_title'];
        $title = $this->removeRequestTags($title);
        $title = $this->prependRequestTag($title, ucfirst($tagName));
        $this->Topic->update($topicId, ['topic_title' => $title]);
    }/*}}}*/

    public function removeRequestTags($strn)/*{{{*/
    {
        $ptn = '#\s*((\(|\[|\{)(accepted|closed|fulfilled|request|solved)(\)|\]|\}))\s*#is';
        return preg_replace($ptn, '', $strn);
    }/*}}}*/

    public function prependRequestTag($strn, $tag)/*{{{*/
    {
        return "[$tag] " . $strn;
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

    public function getUserData($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sql = 'SELECT user_id, user_colour, username, snp_req_n_solve FROM '.USERS_TABLE." WHERE user_id=${userId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
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
