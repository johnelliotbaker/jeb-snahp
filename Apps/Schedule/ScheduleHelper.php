<?php
namespace jeb\snahp\Apps\Schedule;

class ScheduleHelper
{
    public function __construct($config, $cache, $Reputation, $sauth)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->Reputation = $Reputation;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
    }

    public function resetReputationPool()
    {
        $key = "reputation-schedules";
        if ($this->cache->get($key)) {
            return;
        }
        $this->cache->put($key, true, 3600);
        // old procedure: /var/www/forum/ext/jeb/snahp/controller/cron.php
        if (
            $this->config["snp_rep_b_master"] &&
            $this->config["snp_rep_b_cron_giveaway"]
        ) {
            $repMinimumTarget = (int) $this->config["snp_rep_giveaway_minimum"];
            $repGiveawayLastTime =
                (int) $this->config["snp_rep_giveaway_last_time"];
            $repGiveawayDuration =
                (int) $this->config["snp_rep_giveaway_duration"];
            if (time() > $repGiveawayLastTime + $repGiveawayDuration) {
                $this->Reputation->set_min($repMinimumTarget);
            }
        }
    }
}
