<?php
namespace jeb\snahp\core\giftbox;
use \Symfony\Component\HttpFoundation\JsonResponse;

class helper
{
    protected $db;
    protected $auth;
    protected $user;
    protected $config;
    protected $container;
    protected $this_user_id;
    protected $bank_user_account;
    protected $product_class;
    protected $user_inventory;
    protected $invite_helper;
    protected $market_transaction_logger;
    public function __construct(
        $db, $user, $auth, $config, $container,
        $tbl,
        $sauth,
        $bank_user_account, $product_class, $user_inventory,
        $invite_helper, $market_transaction_logger
    )
    {/*{{{*/
        $sauth->reject_anon();
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->product_class = $product_class;
        $this->bank_user_account = $bank_user_account;
        $this->user_inventory = $user_inventory;
        $this->invite_helper = $invite_helper;
        $this->market_transaction_logger = $market_transaction_logger;
        $this->user_id = (int) $this->user->data['user_id'];
        $this->event_name = 'christmas_2019';
        $this->event_display_name= '2019 Christmas Giveaway';
        $this->event_def = $container-> getParameter('jeb.snahp.giftbox')[$this->event_name];
        $this->item_defs = $this->event_def['items'];
        $this->cycle_time = (int) $this->config['snp_giv_cycle_time'];
        $this->max_gift = 10;
        // $this->cycle_time = 20;
    }/*}}}*/

// Public Functions to be called

    public function send_message($data) {/*{{{*/
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }/*}}}*/

    public function set_cycle_time($cycle_time)/*{{{*/
    {
        $this->sauth->reject_non_dev();
        $this->config->set('snp_giv_cycle_time', (int) $cycle_time);
        return true;
    }/*}}}*/

    private function count_received_gift($user_id)/*{{{*/
    {
        $user_id = (int) $user_id;
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->tbl['giveaways'] . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return isset($row['total']) ? $row['total'] : 0;
    }/*}}}*/

    private function is_gift_count_excess($user_id)/*{{{*/
    {
        return $this->count_received_gift($user_id) >= $this->max_gift;
    }/*}}}*/

    private function time_to_next_interval($time, $start_time, $end_time, $latest_unwrap_time)
    {
        $interval = max($this->cycle_time, 1);
        $range = range($start_time, $end_time, $interval);
        for ($i = 0; $i < count($range)-1; $i++) {
            [$s, $e] = [$range[$i], $range[$i+1]];
            if ($time >= $s && $time < $e && $latest_unwrap_time >= $s && $latest_unwrap_time < $e)
            {
                return [false, $e-$time];
            }
        }
        return [true, 0];
    }

    public function get_unwrap_status($user_id)/*{{{*/
    {
        $bin_type = 'from-last-prize';
        // if ($this->sauth->is_dev()) return ['ready', 0];
        if ($this->is_gift_count_excess($user_id)) return ['after', 0];
        $start_time = $this->config['snp_giv_start_time'];
        $end_time = $this->config['snp_giv_end_time'];
        $time = time();
        if ($time < $start_time)
        {
            return ['before', $start_time-$time];
        }
        elseif ($time >= $end_time)
        {
            return ['after', 86400];
        }
        $user_id = (int) $user_id;
        $sql = 'SELECT id, created_time FROM ' . $this->tbl['giveaways'] . " WHERE user_id=${user_id} ORDER BY id DESC LIMIT 1";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) { return ['ready', 0]; }
        $created_time = $row['created_time'];
        if ($bin_type==='from-last-prize')
        {
            $time_left = max($created_time + $this->cycle_time - time(), 0);
            return [$time_left<=0 ? 'ready' : 'not_ready', $time_left];
        }
        [$ready, $time_left] = $this->time_to_next_interval($time, $start_time, $end_time, $created_time);
        return [$ready ? 'ready' : 'not_ready', $time_left];
    }/*}}}*/

    public function can_unwrap($user_id)/*{{{*/
    {
        [$status, $t] = $this->get_unwrap_status($user_id);
        return $status==='ready';
    }/*}}}*/

    public function simulate($n=10000)/*{{{*/
    {
        return false;
        $user_id = $this->user_id;
        $js = new \phpbb\json_response();
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        for ($i = 0; $i < $n; $i++) {
            if ($i%10000==0)
            {
                $this->send_message(['i'=>$i]);
            }
            $item_def = $this->randomly_generate_item();
            $extra = [];
            $item_type = $item_def['type'];
            // $user_id = 2;
            $this->insert_item($item_type, $user_id, $extra);
        }
        return $item_def;
    }/*}}}*/

    private function apply_streak($user_id, $item_def)/*{{{*/
    {
        $item_type = $item_def['type'];
        $job_queue = $item_def['job_queue'];
        if ($item_type !== 'common')
        {
            return $job_queue;
        }
        $rowset = $this->get_user_history($user_id);
        $count = 1;
        foreach ($rowset as $row)
        {
            $latest = $row['item_name'];
            if ($latest === $item_type)
            {
                $count += 1;
            }
            else
            {
                break;
            }
        }
        $count = min($count, 10);
        if ($count > 2)
        {
            $job_queue['apply_streak_multiplier'] = [
                'display_name' => "x${count} Streak Multiplier",
                'level' => $count
            ];
        }
        return $job_queue;
    }/*}}}*/

    public function give_random_item()/*{{{*/
    {
        $user_id = $this->user_id;
        $b_unwrap = $this->can_unwrap($user_id);
        if (!$b_unwrap) { return false; };
        $item_def = $this->randomly_generate_item($user_id);
        $extra = [];
        $item_type = $item_def['type'];
        $job_queue = $this->apply_streak($user_id, $item_def);
        $this->insert_item($item_type, $user_id, $extra);
        $this->process_job_queue($job_queue, $user_id);
        return $item_def;
    }/*}}}*/

    private function is_after_double_time()/*{{{*/
    {
        // Hardcode double time as 1 day before end time
        // see randomly_choose_item() for follow up
        $end_time = (int) $this->config['snp_giv_end_time'];
        $double_time = $end_time - 86400;
        if (!$double_time || $double_time < 1) return false;
        return time() > $double_time;
    }/*}}}*/

    private function randomly_generate_item()/*{{{*/
    {
        $mode = 'normal';
        if ($this->is_after_double_time())
        {
            $mode = 'double';
        }
        $item_type = $this->randomly_choose_item($mode);
        $item_def = $this->item_defs[$item_type];
        return $item_def;
    }/*}}}*/

    public function get_user_history($user_id=null)/*{{{*/
    {
        $user_id = $user_id===null ? $user_id=$this->user_id : (int) $user_id;;
        $sql = 'SELECT item_name FROM ' . $this->tbl['giveaways'] . " WHERE user_id=${user_id} ORDER BY id DESC LIMIT 10";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    private function insert_item($item_name, $user_id, $extra=[])/*{{{*/
    {
        $data = [
            'user_id' => $user_id,
            'type_name' => $this->event_name,
            'item_name' => (string) $item_name,
            'created_time' => time(),
            'data' => serialize($extra)
        ];
        $sql = 'INSERT INTO ' . $this->tbl['giveaways'] . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    private function process_job_queue($job_queue, $user_id)/*{{{*/
    {
        foreach($job_queue as $job_name => $job_data)
        {
            $callback_name = 'process_' . $job_name;
            $this->{$callback_name}($job_data, $user_id);
        }
    }/*}}}*/

    private function randomly_choose_item($mode='normal')/*{{{*/
    {
        foreach (array_reverse($this->item_defs) as $key => $item) {
            if ($mode==='double')
            {
                $rand = mt_rand(1, max(1, (int) (0.01 *$item['probability'])));
            }
            else
            {
                $rand = mt_rand(1, $item['probability']);
            }
            if ($rand == 1)
            {
                return $key;
            }
        }
    }/*}}}*/

    private function deposit_fund($user_id, $amount, $comment)/*{{{*/
    {
        $this->bank_user_account->create_transaction_and_deposit($amount, $user_id, -1, $comment);
    }/*}}}*/

    private function process_giveaway_custom_rank($jobdata, $user_id)/*{{{*/
    {
        $comment = $this->event_display_name . " (${jobdata['display_name']})";
        $this->user_inventory->do_add_item_with_logging(
            $product_class_id=1, $quantity=1, $user_id,
            $extra_comment=$comment);
    }/*}}}*/

    private function process_giveaway_shatners($jobdata, $user_id)/*{{{*/
    {
        $amount = $jobdata['amount'];
        $display_name = $jobdata['display_name'];
        $comment = "Deposit $${amount} for 2019 Christmas Giveaway (${display_name})";
        $this->deposit_fund($user_id, $amount, $comment);
    }/*}}}*/

    private function process_giveaway_invites($jobdata, $user_id)/*{{{*/
    {
        $logger = $this->market_transaction_logger;
        $amount = $jobdata['amount'];
        $comment = $this->event_display_name . " (${jobdata['display_name']})";
        $display_name = $jobdata['display_name'];
        if (!$this->invite_helper->select_invite_user($user_id))
        {
            $this->invite_helper->insert_invite_users([$user_id]);
        }
        $this->invite_helper->increase_invite_points($user_id, $amount);
        $data = [
            'data' => serialize(['comment' => $comment]),
        ];
        $logger->create_single_item_invoice($user_id, $broker_id=-1, $data);
        // $comment = "Deposit $${amount} for 2019 Christmas Giveaway (${display_name})";
        // $this->deposit_fund($user_id, $amount, $comment);
    }/*}}}*/

    private function process_giveaway_arcana($jobdata, $user_id)/*{{{*/
    {
        $logger = $this->market_transaction_logger;
        $comment = $this->event_display_name . " (${jobdata['display_name']})";
        $data = [
            'data' => serialize(['comment' => $comment]),
        ];
        $logger->create_single_item_invoice($user_id, $broker_id=-1, $data);
        // $comment = "Deposit $${amount} for 2019 Christmas Giveaway (${display_name})";
        // $this->deposit_fund($user_id, $amount, $comment);
    }/*}}}*/

    private function process_apply_streak_multiplier($jobdata, $user_id)/*{{{*/
    {
        $level = $jobdata['level'];
        $multiplier_def = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 500,
            4 => 1000,
            5 => 2000,
            6 => 3000,
            7 => 9000,
            8 => 12000,
            9 => 20000,
            10 => 50000
        ];
        $amount = $multiplier_def[$level];
        $display_name = $jobdata['display_name'];
        $comment = "Deposit $${amount} for 2019 Christmas Giveaway (${display_name})";
        $this->deposit_fund($user_id, $amount, $comment);
    }/*}}}*/

    public function setup_block_data()/*{{{*/
    {
        $user_id = $this->user_id;
        $tbl = $this->tbl;
        $params = $this->container->getParameter('jeb.snahp.giftbox');
        $block_status = $params['status']['block'];
        $sql = 'SELECT blocked_id FROM ' . $tbl['foe'] . " WHERE blocker_id=${user_id} AND status=${block_status}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $this->block_data = array_map(function($arg){return $arg['blocked_id'];}, $rowset);
        $this->block_data[] = $user_id;
    }/*}}}*/

}
