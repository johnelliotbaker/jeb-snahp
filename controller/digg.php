<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class digg extends base
{

    protected $topic_id = 0;
    // protected $broadcast_delay = 86400; // 24*60*60
    protected $broadcast_delay = 0; // 24*60*60
    protected $base_url = '';
    protected $topic_data = [];

    public function __construct()/*{{{*/
    {
    }/*}}}*/

	public function handle($mode)/*{{{*/
	{
        $this->reject_anon();
        if (!$this->config['snp_b_snahp_notify'])
        {
            trigger_error('You cannot use digg when notification system is disabled. Error Code: 64be46f11b');
        }
        if (!$this->config['snp_digg_b_master'])
        {
            trigger_error('digg system is currently disabled. Error Code: 2e2b753c43');
        }
        if (!$this->config['snp_digg_b_notify'])
        {
            trigger_error('digg notification system is currently disabled. Error Code: dcfb0260c2');
        }
        $topic_id = $this->request->variable('t', 0);
        if (!$topic_id)
        {
            trigger_error('You must provide a topic id. Error Code: 6a46634247');
        }
        $topic_data = $this->select_topic($topic_id);
        if (!$topic_data)
        {
            trigger_error('That topic does not exist. Error Code: a26b2222d0');
        }
        $this->topic_id = (int) $topic_id;
        $this->topic_data = $topic_data;
        $b_listings = $this->is_listing($topic_data);
        if (!$b_listings)
        {
            trigger_error('You can only digg in listings. Error Code: 924e331d93');
        }
		$this->user->add_lang_ext('jeb/snahp', 'common');
		$this->page_title = 'Digg';
        $this->base_url = '/viewtopic.php?t=' . $topic_id;
        $cfg = [];
        switch ($mode)
        {
        case 'unregister':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->unregister($cfg);
            break;
        case 'register':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->register($cfg);
            break;
        case 'subscribe':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->subscribe($cfg);
            break;
        case 'unsubscribe':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->unsubscribe($cfg);
            break;
        case 'broadcast':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->broadcast($cfg);
            break;
        default:
            trigger_error('Invalid request category. Error Code: 57f43ea934');
            break;
        }
	}/*}}}*/

    public function subscribe($cfg)/*{{{*/
    {
        $this->subscribe_digg($this->topic_id);
        meta_refresh(2, $this->base_url);
        trigger_error('You are now subscribed to this topic.');
    }/*}}}*/

    public function subscribe_digg($topic_id)/*{{{*/
    {
        $user_id = $this->user->data['user_id'];
        $this->upsert_digg_slave($topic_id);
    }/*}}}*/

    public function unsubscribe($cfg)/*{{{*/
    {
        $this->unsubscribe_digg($this->topic_id);
        meta_refresh(2, $this->base_url);
        trigger_error('You have unsubscribed from this topic.');
    }/*}}}*/

    public function unsubscribe_digg($topic_id)/*{{{*/
    {
        $b_op = $this->is_op($this->topic_data);
        $user_id = $this->user->data['user_id'];
        $this->delete_digg_slave($topic_id, "user_id={$user_id}");
        $this->notification->delete_notifications(
            'jeb.snahp.notification.type.digg', $topic_id, false, $user_id
        );
    }/*}}}*/

    public function unregister($cfg)/*{{{*/
    {
        $this->unregister_digg($this->topic_id);
        meta_refresh(2, $this->base_url);
        trigger_error('Your digg was unregistered successfully.');
    }/*}}}*/

    public function unregister_digg($topic_id)/*{{{*/
    {
        $b_op = $this->is_op($this->topic_data);
        if (!$b_op)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('Only opening poster can unregister. Error Code: 001ce75c61');
        }
        $master_data = $this->select_digg_master($topic_id);
        if (!$master_data)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('Digg does not exist. Error Code: ec2743658e');
        }
        $this->delete_digg_master($topic_id);
        $this->delete_digg_slave($topic_id);
        $this->notification->delete_notifications(
            'jeb.snahp.notification.type.digg', $topic_id, false, false
        );
    }/*}}}*/

    public function register($cfg)/*{{{*/
    {
        $this->register_digg($this->topic_id);
        meta_refresh(2, $this->base_url);
        trigger_error('Your digg was registered successfully.');
    }/*}}}*/

    public function register_digg($topic_id)/*{{{*/
    {
        $b_op = $this->is_op($this->topic_data);
        if (!$b_op)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('Only opening poster can register. Error Code: eca16dc835');
        }
        $master_data = $this->select_digg_master($topic_id);
        if ($master_data)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('You have already registered this digg. Error Code: 28c3e2247b');
        }
        $this->upsert_digg_master($topic_id);
    }/*}}}*/

    public function broadcast($cfg)/*{{{*/
    {
        $this->broadcast_digg($this->topic_id);
        meta_refresh(2, $this->base_url);
        trigger_error('Your digg was broadcast successfully.');
    }/*}}}*/

    public function broadcast_digg($topic_id)/*{{{*/
    {
        $b_op = $this->is_op($this->topic_data);
        if (!$b_op)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('Only opening poster can broadcast. Error Code: 763724b64a');
        }
        $master_data = $this->select_digg_master($topic_id);
        if (!$master_data)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('You must first register a topic. Error Code: 67b4b00ad1');
        }
        $broadcast_time = $master_data['broadcast_time'];
        $allowed_time = $broadcast_time + $this->broadcast_delay;
        if (time() < $allowed_time)
        {
            meta_refresh(4, $this->base_url);
            trigger_error('You need to wait until ' . $this->user->format_date($allowed_time) . ' to broadcast the update.');
        }
        $this->upsert_digg_master($topic_id);
        $this->notification->delete_notifications(
            'jeb.snahp.notification.type.digg', $topic_id, false, false
        );
        $this->notification->add_notifications (
            'jeb.snahp.notification.type.digg', $this->topic_data
        );
    }/*}}}*/

    private function delete_digg_slave($topic_id, $where='1=1')/*{{{*/
    {
        $where = $this->db->sql_escape($where);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'DELETE FROM ' . $tbl['digg_slave'] . "
            WHERE topic_id={$topic_id} AND $where";
        $this->db->sql_query($sql);
    }/*}}}*/

    private function delete_digg_master($topic_id)/*{{{*/
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'DELETE FROM ' . $tbl['digg_master'] . ' WHERE topic_id=' . (int) $topic_id;
        $this->db->sql_query($sql);
    }/*}}}*/

    private function upsert_digg_slave($topic_id)/*{{{*/
    {
        $user_id = $this->user->data['user_id'];
        $data = [
            'topic_id'       => $topic_id,
            'user_id'        => $user_id,
        ];
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'INSERT INTO ' . $tbl['digg_slave'] . $this->db->sql_build_array('INSERT', $data) . "
            ON DUPLICATE KEY UPDATE user_id={$user_id}";
        $this->db->sql_query($sql);
    }/*}}}*/

    private function upsert_digg_master($topic_id)/*{{{*/
    {
        $user_id = $this->user->data['user_id'];
        $data = [
            'topic_id'       => $topic_id,
            'user_id'        => $user_id,
            'broadcast_time' => 0,
        ];
        $broadcast_time = time();
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'INSERT INTO ' . $tbl['digg_master'] . $this->db->sql_build_array('INSERT', $data) . "
            ON DUPLICATE KEY UPDATE broadcast_time={$broadcast_time}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function send_message($data) {/*{{{*/
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }/*}}}*/

}
