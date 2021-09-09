<?php
namespace jeb\snahp\Apps\Toplist;

const CACHE_TIMEOUT = 3600;


class ReputationToplist extends BaseToplist
{
    const NAME = 'reputation';
    const USER_COLUMN_TARGET_NAME = 'snp_rep_n_received';
    const CONFIG_TEXT_NAME = "snp_toplist_reputation_blacklist";
}

class ThanksToplist extends BaseToplist
{
    const NAME = 'thanks';
    const USER_COLUMN_TARGET_NAME = 'snp_thanks_n_received';
    const CONFIG_TEXT_NAME = "snp_toplist_thanks_blacklist";
}

class RequestsSolvedToplist extends BaseToplist
{
    const NAME = 'requests_solved';
    const USER_COLUMN_TARGET_NAME = 'snp_req_n_solve';
    const CONFIG_TEXT_NAME = "snp_toplist_requests_solved_blacklist";
}

class ToplistHelper
{
    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $configText,
        $cache,
        $sauth,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->configText = $configText;
        $this->cache = $cache;
        $this->sauth = $sauth;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function getToplist($name)
    {
        $tl = $this->toplistFactory($name);
        $cacheName = "snp_${name}_toplist";
        if (!$list = $this->cache->get($cacheName)) {
            $list = $tl->getList();
            $this->cache->put($cacheName, $list, CACHE_TIMEOUT);
        }
        return $list;
    }

    public function getThanksToplist()
    {
        return $this->getToplist('thanks');
    }

    public function getRequestSolvedToplist()
    {
        return $this->getToplist('requests_solved');
    }

    public function getReputationToplist()
    {
        return $this->getToplist('reputation');
    }

    public function toplistFactory($name)
    {
        switch ($name) {
        case 'thanks':
            return new ThanksToplist($this->QuerySetFactory, $this->configText);
        case 'requests_solved':
            return new RequestsSolvedToplist($this->QuerySetFactory, $this->configText);
        case 'reputation':
            return new ReputationToplist($this->QuerySetFactory, $this->configText);
        default:
        }
        throw new \Exception("Invalid toplist name ${name}. Error Code: 154889db86");
    }

    public function blacklist($name, $userId)
    {
        $tl = $this->toplistFactory($name);
        $tl->blacklistUsers([$userId]);
    }

    public function whitelist($name, $userId)
    {
        $tl = $this->toplistFactory($name);
        $tl->whitelistUsers([$userId]);
    }
}

class BaseToplist
{
    public function __construct($QuerySetFactory, $configText)
    {
        $this->QuerySetFactory = $QuerySetFactory;
        $this->configText = $configText;
    }

    public function getList()
    {
        $colName = $this::USER_COLUMN_TARGET_NAME;
        $blacklist = $this->getBlacklistedUsers();
        $sqlArray = [
            'SELECT'    => "a.user_id, a.username, a.user_colour, a.{$colName}",
            'FROM'      => [ USERS_TABLE => 'a', ],
            'ORDER_BY'  => "a.{$colName} DESC",
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $queryset->filterInSet('a.user_id', $blacklist, true);
        return $queryset->slice(0, 10, $many = true, $cacheTimeout = 3600);
    }

    public function getBlacklistedUsers($asList=true)
    {
        $cfgName = $this::CONFIG_TEXT_NAME;
        if ($data = $this->configText->get($cfgName)) {
            $data = unserialize($data);
            return $asList ? array_keys($data) : $data;
        }
        return [];
    }

    public function blacklistUsers($userIds)
    {
        $blacklist = $this->getBlacklistedUsers($asList = false);
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            $blacklist[$userId] = true;
        }
        $this->configText->set(
            $this::CONFIG_TEXT_NAME,
            serialize($blacklist)
        );
        return [];
    }

    public function whitelistUsers($userIds)
    {
        $blacklist = $this->getBlacklistedUsers($asList = false);
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            unset($blacklist[$userId]);
        }
        $this->configText->set(
            $this::CONFIG_TEXT_NAME,
            serialize($blacklist)
        );
        return [];
    }
}
