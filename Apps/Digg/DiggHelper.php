<?php
namespace jeb\snahp\Apps\Digg;

class DiggHelper
{
    public function __construct(
        $request,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->request = $request;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function getUserDiggSlave($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sqlArray = [
            'SELECT'    => 'a.*, b.topic_title, c.broadcast_time',
            'FROM'      => [ $this->tbl['digg_slave'] => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM' => [TOPICS_TABLE => 'b'],
                    'ON' => 'a.topic_id=b.topic_id'
                ],
                [
                    'FROM' => [$this->tbl['digg_master'] => 'c'],
                    'ON' => 'a.topic_id=c.topic_id'
                ],
            ],
            'WHERE'     => "a.user_id={$userId}",
            'ORDER_BY'  => 'c.broadcast_time DESC, a.topic_id DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $this->request);
        return $this->paginator->getPaginatedResult($results);
    }/*}}}*/

    public function getUserDiggMaster($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sqlArray = [
            'SELECT'    => 'a.*, b.topic_title',
            'FROM'      => [ $this->tbl['digg_master'] => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM' => [TOPICS_TABLE => 'b'],
                    'ON' => 'a.topic_id=b.topic_id'
                ]
            ],
            'WHERE'     => "a.user_id={$userId}",
            'ORDER_BY'  => 'a.broadcast_time DESC, a.topic_id DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $this->request);
        return $this->paginator->getPaginatedResult($results);
    }/*}}}*/
}
