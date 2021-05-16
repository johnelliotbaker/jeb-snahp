<?php
namespace jeb\snahp\Apps\Toplist;

class ToplistHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;

    protected $db;/*{{{*/
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
    }/*}}}*/

    public function getThanksToplist()/*{{{*/
    {
        $sqlArray = [
            'SELECT'    => 'a.user_id, a.username, a.user_colour, a.snp_thanks_n_received',
            'FROM'      => [ USERS_TABLE => 'a', ],
            'ORDER_BY'  => 'a.snp_thanks_n_received DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        return $queryset->slice(0, 10, $many = true, $cacheTimeout = 3600);
    }/*}}}*/

    public function getRequestSolvedToplist()/*{{{*/
    {
        $sqlArray = [
            'SELECT'    => 'a.user_id, a.username, a.user_colour, a.snp_req_n_solve',
            'FROM'      => [ USERS_TABLE => 'a', ],
            'ORDER_BY'  => 'a.snp_req_n_solve DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        return $queryset->slice(0, 10, $many = true, $cacheTimeout = 3600);
    }/*}}}*/

    public function getReputationToplist()/*{{{*/
    {
        $sqlArray = [
            'SELECT'    => 'a.user_id, a.username, a.user_colour, a.snp_rep_n_received',
            'FROM'      => [ USERS_TABLE => 'a', ],
            'ORDER_BY'  => 'a.snp_rep_n_received DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        return $queryset->slice(0, 10, $many = true, $cacheTimeout = 3600);
    }/*}}}*/

}
