<?php
namespace jeb\snahp\Apps\QuickUCP;

class QuickUCPHelper
{
    public function __construct(
        $db,
        $container,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        // $this->paginator = $pageNumberPagination;
        // $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function getSettings($userId)/*{{{*/
    {
        $fields = 'user_id, username, user_colour, snp_ded_show_in_search';
        $User = $this->container->get('jeb.snahp.Apps.Core.Db.query.User');
        return $User->get($userId, ["fields" => $fields]);
    }/*}}}*/

    public function setSettings($userId, $data)/*{{{*/
    {
        foreach ($data as $key => $value) {
            setSettingsContextually($userId, $key, $value);
        }
        return $this->getSettings($userId, $data);
    }/*}}}*/
}

function setSettingsContextually($userId, $key, $value)
{
    switch ($key) {
    case 'snp_ded_show_in_search':
        global $phpbb_container;
        $helper = $phpbb_container->get('jeb.snahp.Apps.DeadLinks.DeadLinksHelper');
        $helper->setDeadlinksVisibilityInSearch($userId, $value);
    default:
    }
}
