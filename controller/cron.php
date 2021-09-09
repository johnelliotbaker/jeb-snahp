<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use ger\feedpostbot\controller;
use \ger\feedpostbot\classes\driver;

class cron extends base
{

    protected $base_url = '';
    protected $db;
    protected $cron_helper;
    protected $feedpostbot;
    protected $reputation_helper;

    public function __construct($table_prefix, $var1,
        $db,
        $cron_helper,
        $feedpostbot,
        $reputation_helper,
        $jukeboxHelper
    )
    {
        $this->table_prefix = $table_prefix;
        $this->db = $db;
        $this->cron_helper = $cron_helper;
        $this->feedpostbot = $feedpostbot;
        $this->reputation_helper = $reputation_helper;
        $this->jukeboxHelper = $jukeboxHelper;
    }

    public function handle($mode)
    {
        $this->reject_non_dev();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->user_id = $this->user->data['user_id'];
        switch ($mode)
        {
        case 'hourly':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->hourly($cfg);
            break;
        case 'search':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->search($cfg);
            break;
        default:
            trigger_error('Error Code: 12e1ae39b6');
            break;
        }
    }

    private function hourly($cfg)
    {
        // Feedpostbot fetch
        $this->cron_helper->log('feedpostbot', 'hourly', 'before');
        $this->feedpostbot->fetch_all();
        $this->cron_helper->log('feedpostbot', 'hourly', 'after');
        // Jukebox Updates
        // $this->cron_helper->log('jukebox', 'hourly', 'before');
        $this->jukeboxHelper->updateList();
        // $this->cron_helper->log('jukebox', 'hourly', 'after');
        // Giving reputation points
        if ($this->config['snp_rep_b_master'] && $this->config['snp_rep_b_cron_giveaway'])
        {
            $rep_minimum_target     = (int) $this->config['snp_rep_giveaway_minimum'];
            $rep_giveaway_last_time = (int) $this->config['snp_rep_giveaway_last_time'];
            $rep_giveaway_duration  = (int) $this->config['snp_rep_giveaway_duration'];
            if (time() > $rep_giveaway_last_time + $rep_giveaway_duration)
            {
                $this->cron_helper->log('reputation', 'hourly', 'before');
                $this->reputation_helper->set_min($rep_minimum_target);
                $this->cron_helper->log('reputation', 'hourly', 'after');
            }
        }
        trigger_error('Following hourly task was performed.
                        <p>1) feedpostbot->fetch_all</p>
                        <p>2) spotlight->update_list</p>
                        <p>3) jukebox->updateList</p>
                        <p>4) reputation->set_min</p>
                        ');
    }

}
