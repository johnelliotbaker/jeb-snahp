<?php
namespace jeb\snahp\core\Thanks;

class ThanksUsers
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;
    const DEBUG = true;

    protected $db;/*{{{*/
    protected $user;
    protected $config;
    protected $template;
    protected $tbl;
    protected $sauth;
    private $data;
    public function __construct(
        $db,
        $user,
        $config,
        $template,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->template = $template;
        $this->tbl = $tbl;
        $this->myTbl = $tbl['thanks_users'];
        $this->sauth = $sauth;
        $this->data = [];
        $this->nAllowedPerCycle = 2;
        $this->cycleInSeconds = (int) $config['snp_thanks_cycle_duration'];
        $this->userId = $this->user->data['user_id'];
    }/*}}}*/

    public function hasGivenAllAvailableThanks($userId)/*{{{*/
    {
        $exempted = $this->sauth->is_dev();
        if ($exempted) {
            return false;
        }
        if (!$this->data) {
            $this->setup($userId);
        }
        $curr = time();
        $oldest = $this->getOldestTimestamp($this->data);
        $allowedAfter = $oldest['t'] + $this->cycleInSeconds;
        if ($curr < $allowedAfter) {
            return true;
        }
        return false;
    }/*}}}*/

    public function setup($userId)/*{{{*/
    {
        $this->nAllowedPerCycle = $this->getCycleLimit($userId);
        $row = $this->getOrCreateThanksUser($userId);
        if (!$this->validateOrResetUserTimestamps($userId, $row)) {
            $row = $this->getThanksUser($userId);
        }
        $this->data = $row;
        return $row;
    }/*}}}*/

    public function getCycleLimit($userId)/*{{{*/
    {
        return $this->sauth->getMaxFromGroupMemberships($userId, 'tfp_n_per_cycle');
    }/*}}}*/

    private function getOrCreateThanksUser($userId)/*{{{*/
    {
        $row = $this->getThanksUser($userId);
        if (!$row) {
            $row = $this->createThanksUser($userId);
            if (!$row) {
                trigger_error(
                    'Could not create thanks user. Error Code: ccf90b4d4b'
                );
            }
        }
        return $row;
    }/*}}}*/

    public function getThanksUser($userId)/*{{{*/
    {
        $tbl = $this->tbl['thanks_users'];
        $sql = 'SELECT * FROM ' . $tbl . " WHERE user_id=${userId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    private function createThanksUser($userId)/*{{{*/
    {
        $tbl = $this->tbl['thanks_users'];
        $data = [
            'user_id' => (int) $userId,
            'b_enable' => 1,
            'timestamps' => serialize([]),
        ];
        $sql = 'INSERT INTO ' . $tbl . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        $this->resetUserTimestamps($userId);
        return $this->getThanksUser($userId);
    }/*}}}*/

    private function makeTimestamp($topicId, $time=null)/*{{{*/
    {
        return [
            't' => $time===null ? time() : (int) $time,
            'tid' => $topicId,
        ];
    }/*}}}*/

    private function makeNewTimestamps($nAllowed)/*{{{*/
    {
        $data = [];
        for ($i = 0; $i < $nAllowed; $i++) {
            $data[] = $this->makeTimestamp(0, 0);
        }
        return $data;
    }/*}}}*/

    public function resetUserTimestamps($userId)/*{{{*/
    {
        $row = $this->getOrCreateThanksUser($userId);
        $timestamps = $row['timestamps'];
        $nAllowed = $this->nAllowedPerCycle;
        $timestamps = $this->makeNewTimestamps($nAllowed);
        $data = ['timestamps' => serialize($timestamps)];
        $tbl = $this->tbl['thanks_users'];
        $sql = 'UPDATE ' . $tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id=${userId}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    private function validateOrResetUserTimestamps($userId, $data=null)/*{{{*/
    {
        if ($data===null) {
            $data = $this->getThanksUser($userId);
        }
        $timestamps = unserialize($data['timestamps']);
        if (count($timestamps) != $this->nAllowedPerCycle) {
            $this->resetUserTimestamps($userId);
            return false;
        }
        return true;
    }/*}}}*/

    private function getOldestTimestamp($thanks_user_data)/*{{{*/
    {
        return unserialize($thanks_user_data['timestamps'])[0];
    }/*}}}*/

    public function getUserTimestamps($userId)/*{{{*/
    {
        $data = $this->getThanksUser($userId);
        return unserialize($data['timestamps']);
    }/*}}}*/

    public function getCycleDuration()/*{{{*/
    {
        return (int) $this->config['snp_thanks_cycle_duration'];
    }/*}}}*/

    public function rejectBannedUser($user_id)/*{{{*/
    {
        if ($this::DEBUG) return false;
        $permitted = $this->sauth->is_dev();
        if ($permitted) {
            return false;
        }
        if (!$this->data['b_enable']) {
            trigger_error(
                'You are banned from using the thanks system. Error Code: 1c13b9cea3'
            );
        }
    }/*}}}*/

    public function rejectExcessiveThanksPerCycle($thanksUserData)/*{{{*/
    {
        // TODO: is_dev_or_op
        $permitted = $this->sauth->is_dev();
        if ($permitted) {
            return false;
        }
        if ($this->nAllowedPerCycle < 1) {
            trigger_error('You cannot give any more thanks. Error Code: 432e131525');
        }
        $curr = time();
        $oldest = $this->getOldestTimestamp($thanksUserData);
        $allowedAfter = $oldest['t'] + $this->cycleInSeconds;
        if ($curr < $allowedAfter) {
            $datestrn = $this->user->format_date($allowedAfter);
            trigger_error('You cannot give any more thanks until ' . $datestrn);
        }
    }/*}}}*/

    public function addThanksGivenStatistics($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sql = 'UPDATE ' . USERS_TABLE
            . ' SET snp_thanks_n_given=snp_thanks_n_given+1 WHERE user_id='
            . $userId;
        $this->db->sql_query($sql);
    }/*}}}*/

    public function addThanksReceivedStatistics($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sql = 'UPDATE ' . USERS_TABLE
            . ' SET snp_thanks_n_received=snp_thanks_n_received+1 WHERE user_id='
            . $userId;
        $this->db->sql_query($sql);
    }/*}}}*/

    public function insertTimestamp($userId, $topicId)/*{{{*/
    {
        $timestamps = array_slice(
            unserialize($this->data['timestamps']), 1, $this->nAllowedPerCycle-1
        );
        $timestamps[] = $this->makeTimestamp($topicId);
        $tbl = $this->tbl['thanks_users'];
        $data = ['timestamps' => serialize($timestamps)];
        $sql = 'UPDATE ' . $tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $data)
            . " WHERE user_id=${userId}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

}
