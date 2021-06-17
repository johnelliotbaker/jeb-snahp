<?php
namespace jeb\snahp\Apps\DeadLinks;

class DeadLinksHelper
{
    public function __construct($config, $sauth)/*{{{*/
    {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
    }/*}}}*/

    public function appendGraveyardToExFidAry($exFidAry)/*{{{*/
    {
        $fidGraveyard = [(int) unserialize($this->config['snp_cron_graveyard_fid'])['default']] ?? [];
        return array_merge($exFidAry, $fidGraveyard);

    }/*}}}*/
}
