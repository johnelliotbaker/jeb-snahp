<?php
namespace jeb\snahp\Apps\ThanksResetCycle;

class ThanksResetCycleHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;
    const THANKS_RESET_PRODUCT_CLASS_NAME = 'thanks_reset';

    protected $db;/*{{{*/
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    protected $thanksUsers;
    protected $productClass;
    protected $userInventory;
    public function __construct(
        $db,
        $user,
        $template,
        $tbl,
        $sauth,
        $thanksUsers,
        $productClass,
        $userInventory
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->tbl = $tbl;
        $this->rxnTbl = $tbl['ThanksResetCycle'];
        $this->sauth = $sauth;
        $this->thanksUsers = $thanksUsers;
        $this->productClass = $productClass;
        $this->userInventory = $userInventory;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public function hasGivenAllAvailableThanks($userId)/*{{{*/
    {
        return $this->thanksUsers->hasGivenAllAvailableThanks($userId);
    }/*}}}*/

    public function getUserTimestamps($userId)/*{{{*/
    {
        return $this->thanksUsers->getUserTimestamps($userId);
    }/*}}}*/

    public function getThanksResetTokenCount($userId)/*{{{*/
    {
        $product_class_name = $this::THANKS_RESET_PRODUCT_CLASS_NAME;
        $productClassData = $this->productClass
            ->get_product_class_by_name($product_class_name);
        if ($productClassData) {
            $pcid = (int)$productClassData['id'];
            $invData = $this->userInventory
                ->get_single_inventory("product_class_id=${pcid}");
            if ($invData) {
                return (int) $invData['quantity'];
            }
        }
        return 0;
    }/*}}}*/

    public function resetUserTimestamps($userId)/*{{{*/
    {
        return $this->thanksUsers->resetUserTimestamps($userId);
    }/*}}}*/

    public function reduceThanksToken($userId, $tokenCost)/*{{{*/
    {
        $product_class_name = $this::THANKS_RESET_PRODUCT_CLASS_NAME;
        $productClassData = $this->productClass
            ->get_product_class_by_name($product_class_name);
        if (!$productClassData) {
            trigger_error(
                "{$product_class_name} does not exist. Error Code: efe67a05d8"
            );
        }
        $pcid = (int) $productClassData['id'];
        $this->userInventory
            ->doRemoveItemWithLogging($pcid, $tokenCost, $userId);
    }/*}}}*/

    public function getUserThanksInventory($userId)/*{{{*/
    {
        $product_class_name = $this::THANKS_RESET_PRODUCT_CLASS_NAME;
        $productClassData = $this->productClass
            ->get_product_class_by_name($product_class_name);
        if ($productClassData) {
            $pcid = (int)$productClassData['id'];
            $invData = $this->userInventory
                ->get_single_inventory("product_class_id=${pcid}", $userId);
            if ($invData) {
                return $invData;
            }
        }
    }/*}}}*/

    public function getThanksResetTokens($userId)/*{{{*/
    {
        $data = $this->getUserThanksInventory($userId);
        if (!$data || !array_key_exists('quantity', $data)) {
            trigger_error("Invalid thanks user data. Error Code: 51d82c6242");
        }
        return (int) $data['quantity'];
    }/*}}}*/

    public function hasRequiredTokens($userId, $tokenCost=1)/*{{{*/
    {
        $tokens = $this->getThanksResetTokens($userId);
        return $tokens >= $tokenCost;
    }/*}}}*/

    private function timeLeft($oldestTime, $cycleDuration)/*{{{*/
    {
    }/*}}}*/

    public function getRecentThanks($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $timestamps = $this->thanksUsers->getUserTimestamps($userId);
        if (!$timestamps) {
            return [];
        }
        $topicTitleDict = $this->getTopicTitlesAsDict($timestamps);
        $res = [];
        foreach ($timestamps as $timestamp) {
            if (!$timestamp['t']) {
                continue;
            }
            $tid = $timestamp['tid'];
            $timestamp['tt'] = $topicTitleDict[$tid];
            $res[] = $timestamp;
        }
        $now = new \datetime();
        $cycleDuration = $this->thanksUsers->getCycleDuration($userId);
        $res = array_map(
            function ($arg) use ($cycleDuration, $now) {
                $next = $cycleDuration + (int) $arg['t'];
                $next = new \datetime("@$next");
                $arg['tl'] = date_diff($now, $next)->format('%R%dd %hh %im %ss');
                $arg['t'] = $arg['t']
                    ? $this->user->format_date($arg['t'], 'M d, g:i a')
                    : '';
                return $arg;
            },
            $res
        );
        return $res;
    }/*}}}*/

    private function getTopicTitlesAsDict($timestamps)/*{{{*/
    {
        if (!$timestamps) {
            return [];
        }
        $topicIds = array_map(
            function ($arg) {
                return $arg['tid'];
            },
            $timestamps
        );
        $inset = $this->db->sql_in_set('a.topic_id', $topicIds);
        $where = "$inset";
        $sql_ary = [
            'SELECT' => 'a.topic_id, a.topic_title',
            'FROM' => [TOPICS_TABLE => 'a'],
            'WHERE'    => $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $res = [];
        foreach ($rowset as $row) {
            $res[$row['topic_id']] = $row['topic_title'];
        }
        return $res;
    }/*}}}*/
}
