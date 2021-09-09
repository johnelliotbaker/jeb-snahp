<?php
namespace jeb\snahp\Apps\PostingViolation;

class PostingViolationHelper
{
    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
        $this->t = $tbl['posting_violation'];
    }

    public function getPostingViolationsUserToplist($request)
    {
        $whereArray = ['a.snp_violation_count > 0'];
        $username = utf8_clean_string($request->variable('username', ''));
        if ($username) {
            $whereArray[] = "a.username_clean LIKE '${username}%'";
        }
        $where = implode(' AND ', $whereArray);
        $sqlArray = [
            'SELECT' => 'username, user_colour, user_id, snp_violation_count',
            'FROM' => [ USERS_TABLE => 'a', ],
            'WHERE' => $where,
            'ORDER_BY' => 'a.snp_violation_count DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $request);
        return $this->paginator->getPaginatedResult($results);
    }

    public function getUserPostingViolations($username, $request)
    {
        $userId = $this->sauth->userNameToUserId($username);
        $sqlArray = [
            'SELECT' => 'a.*',
            'FROM' => [ $this->t => 'a', ],
            'WHERE' => "a.user_id=${userId}",
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $request);
        return $this->paginator->getPaginatedResult($results);
    }

    public function addPostingViolationEntry($posterId, $postId, $postText)
    {
        $data = ['post_id' => $postId, 'user_id' => $posterId, 'post_text' => $postText];
        $data = $this->db->sql_build_array('INSERT', $data);
        $sql = 'INSERT INTO ' . $this->t .  $data;
        $this->db->sql_query($sql);
    }

    public function markTopicForViolation($topicId, $mark, $reason)
    {
        if (!$this->getTopicData($topicId)) {
            throw new \Exception('Topic not found. Error Code: 92f4e5113d');
        }
        $topicId = (int) $topicId;
        $data = ['snp_violation' => (int) $mark, 'snp_violation_reason' => $reason];
        $data = $this->db->sql_build_array('UPDATE', $data);
        $sql = 'UPDATE ' .TOPICS_TABLE. " SET ${data} WHERE topic_id=${topicId}" ;
        $this->db->sql_query($sql);
    }

    public function incrementUserViolation($userId)
    {
        $sql = 'UPDATE ' . USERS_TABLE . " SET snp_violation_count=snp_violation_count+1 WHERE user_id=${userId}";
        $this->db->sql_query($sql);
    }

    public function isTopicInViolation($topicId)
    {
        $td = $this->getTopicData($topicId);
        return $td['snp_violation'] ? true : false;
    }

    public function submitPostWithConfirmation($violationReason)
    {
        global $request, $template;
        $varnames = $request->variable_names();
        $postData = [];
        foreach ($varnames as $varname) {
            $postData[$varname] = $request->variable($varname, '');
        }
        $sHiddenFields = build_hidden_fields($postData);
        $warningArray = [
            '<h1 style="color: #A00; font-size: 5em;">This topic has been marked for rule violation(s).',
            '<br/><br/>',
        ];
        if ($violationReason) {
            $warningArray[] = ">> $violationReason<br/><br/>";
        }
        $warningArray = array_merge(
            $warningArray, [
            'You will receive a rule violation for replying to this topic.<br/><br/><br/>',
            '</h1>',
            '<p>',
            'It is your responsibility to know and follow the forum rules.<br/>',
            'You may choose to engage in further discussions by clicking the "Yes" button, ',
            'but your account will incur a minimum of one-point violation for each reply. ',
            'If the total violations exceed a limit, ',
            'your account will receive automatic moderation and/or an account ban.',
            '</p><br/>',
            '<p>Select "Yes" to continue, or "No" to return.</p>',
            ]
        );
        $warning = implode('', $warningArray);
        confirm_box(false, $warning, $sHiddenFields);
    }

    public function getTopicData($topicId)
    {
        $topicId = (int) $topicId;
        $sql = 'SELECT * FROM ' . TOPICS_TABLE . " WHERE topic_id=${topicId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }
}
