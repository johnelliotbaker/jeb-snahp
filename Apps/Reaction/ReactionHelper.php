<?php
namespace jeb\snahp\Apps\Reaction;

class ReactionHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;

    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    protected $forumHelper;
    public function __construct(
        $db,
        $user,
        $template,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->tbl = $tbl;
        $this->rxnTbl = $tbl['reaction'];
        $this->sauth = $sauth;
        $this->user_id = $this->user->data['user_id'];
    }

    public function addOrUpdate($userId, $type, $postId, $message='')
    {
        if ($row = $this->getUserPostReaction($userId, $postId)) {
            $this->updateReaction($userId, $type, $postId, $message);
        } else {
            $this->addReaction($userId, $type, $postId, $message);
        }
    }

    private function getUserPostReaction($userId, $postId)
    {
        $sql = "SELECT * FROM {$this->rxnTbl}" .
            " WHERE user_id=$userId AND post_id=$postId";
        $result = $this->db->sql_query($sql, $this::CACHE_DURATION_LONG);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function updateReaction($userId, $type, $postId, $message)
    {
        [$userId, $type, $postId, $message] = [
            (int) $userId, (string) $type, (int) $postId, (string) $message];
        $data = [
            "type" => $type,
            "user_id" => $userId,
            "post_id" => $postId,
            "message" => $message,
            "created" => time(),
        ];
        $sql = "UPDATE {$this->rxnTbl} SET "
            . $this->db->sql_build_array('UPDATE', $data)
            . " WHERE user_id=$userId AND post_id=$postId";
        $this->db->sql_query($sql);
    }

    private function addReaction($userId, $type, $postId, $message)
    {
        [$userId, $type, $postId, $message] = [
            (int) $userId, (string) $type, (int) $postId, (string) $message];
        $data = [
            "type" => $type,
            "user_id" => $userId,
            "post_id" => $postId,
            "message" => $message,
            "created" => time(),
        ];
        $sql = "INSERT INTO {$this->rxnTbl} " .
            $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }

    public function getReactionById($id)
    {
        $id = (int) $id;
        $where = "a.id={$id}";
        $sql_array = [
            'SELECT' => 'a.*, b.username, b.user_colour, b.user_avatar',
            'FROM'      => [ $this->rxnTbl   => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM'  => [USERS_TABLE => 'b'],
                    'ON'    => 'a.user_id=b.user_id',
                ],
            ],
            'WHERE'     => $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql, $this::CACHE_DURATION_LONG);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return [];
        }
        $row['created'] = $this->user->format_date($row['created'], 'M d, g:i a');
        return $row;
    }

    public function getReactions($request, $limit=100)
    {
        $where = $this->makeFilter($request);
        $sql = "SELECT * FROM {$this->rxnTbl} WHERE {$where}";
        $result = $this->db->sql_query_limit($sql, $limit, 0, $this::CACHE_DURATION);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    private function makeFilter($request)
    {
        $postId = $request->variable('p', 0);
        $where = ['1=1'];
        if ($postId) {
            $where[] = "post_id={$postId}";
        }
        return implode(' AND ', $where);
    }

    public function getPostReactions($postId, $limit=100)
    {
        $where = "a.post_id={$postId}";
        $order_by = 'a.id ASC';
        $sql_array = [
            'SELECT' => 'a.id as i, b.user_colour as c, a.type as t',
            'FROM'      => [ $this->rxnTbl   => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM'  => [USERS_TABLE => 'b'],
                    'ON'    => 'a.user_id=b.user_id',
                ],
            ],
            'WHERE'     => $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit(
            $sql,
            $limit,
            0,
            $this::CACHE_DURATION_LONG
        );
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }
}
