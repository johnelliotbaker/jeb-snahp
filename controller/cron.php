<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class cron extends base
{

    protected $base_url = '';
    protected $reputation_controller;

    public function __construct($table_prefix, $var1, $reputation_controller)/*{{{*/
    {
        $this->table_prefix = $table_prefix;
        $this->reputation_controller = $reputation_controller;
    }/*}}}*/

	public function handle($mode)/*{{{*/
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
	}/*}}}*/

    private function hourly($cfg)/*{{{*/
    {
        // Giving reputation points
        if ($this->config['snp_rep_b_master'] && $this->config['snp_rep_b_cron_giveaway'])
        {
            $rep_minimum_target     = (int) $this->config['snp_rep_giveaway_minimum'];
            $rep_giveaway_last_time = (int) $this->config['snp_rep_giveaway_last_time'];
            $rep_giveaway_duration  = (int) $this->config['snp_rep_giveaway_duration'];
            if (time() > $rep_giveaway_last_time + $rep_giveaway_duration)
            {
                $cfg['target'] = $rep_minimum_target;
                $cfg['u_action'] = '/';
                $this->config->set('snp_rep_giveaway_last_time', time());
                // WARNING if the controller script  has trigger_error(),
                // execution will stop!!!
                $this->reputation_controller->mcp_set_min($cfg);
            }
        }
        trigger_error('Finished doing hourly stuff.');
    }/*}}}*/

}
