<?php

namespace jeb\snahp\controller;

use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

class reqs extends base
{
    const SEASONED_SOLVER_THRESHOLD = 300;
    public function __construct()/*{{{*/
    {
        $this->ptn = '#(\(|\[|\{)(accepted|request|closed|fulfilled|solved)(\)|\]|\})\s*#is';
    }/*}}}*/

    public function handle_mcp($mode, $uid)/*{{{*/
    {
        $this->user_id = $this->user->data['user_id'];
        $this->reject_non_moderator();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->def = $this->container->getParameter('jeb.snahp.reqsmcp')['def'];
        $def = $this->def;
        // $this->u_action = "viewtopic.php?f=$fid&t=$tid&p=$pid";
        switch ($mode) {
            case $def['enable']: // Disallow user from dib
                $this->enable_dibber($uid, true);
                meta_refresh(2, '/');
                trigger_error('User is now allowed to dib.');
                break;
            case $def['disable']: // Allow user to dib
                $this->enable_dibber($uid, false);
                meta_refresh(2, '/');
                trigger_error('User is now banned from dibbing.');
                break;
        }
    }/*}}}*/

    public function enable_dibber($uid, $b_enable=true)/*{{{*/
    {
        $enable = $b_enable ? 1 : 0;
        $sql_arr = [
            'snp_req_dib_enable' => $enable,
        ];
        $sql = 'UPDATE ' . USERS_TABLE . ' SET
            ' . $this->db->sql_build_array('UPDATE', $sql_arr) .'
            WHERE user_id=' . $uid;
        $this->db->sql_query($sql);
    }/*}}}*/

    public function handle($fid, $tid, $pid, $mode)/*{{{*/
    {
        $this->user_id = $this->user->data['user_id'];
        $this->b_mod = $this->is_mod();
        $this->reject_anon();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def = $this->def;
        $this->u_action = "viewtopic.php?f=$fid&t=$tid&p=$pid";
        $reqdata = $this->select_request($tid);
        if (!$reqdata) {
            meta_refresh(2, $this->u_action);
            trigger_error('That request does not exist.');
        }
        switch ($mode) {
        case $def['solve']: // To solve request
            $this->rejectInvalidSolver($reqdata);
            $this->solve_request($fid, $tid, $pid, $def['solve'], $reqdata);
            break;
        case $def['terminate']: // To terminate request
            $this->rejectInvalidTerminator($reqdata);
            if ($reqdata['status']==$def['fulfill'] || $reqdata['status']==$def['dib']) {
                $cfg['tpl_name'] = '@jeb_snahp/requests/component/terminate_confirm/base.html';
                $cfg['base_url'] = "/viewtopic.php?f={$fid}&t={$tid}#p{$pid}";
                $cfg['title'] = 'Confirm Request Termination';
                return $this->handle_terminate_confirm($fid, $tid, $pid, $reqdata, $cfg);
            }
            $this->solve_request($fid, $tid, $pid, $def['terminate'], $reqdata);
            break;
        case $def['dib']:
            return $this->dib_request($fid, $tid, $pid);
        case $def['undib']:
            $this->undib_request($fid, $tid, $pid);
            break;
        case $def['fulfill']:
            if ((int) $this->user->data['snp_req_n_solve'] >= $this::SEASONED_SOLVER_THRESHOLD) {
                $this->fulfill_request($fid, $tid, $pid, $fulfilledBySeasonedSolver = true);
                $this->handle($fid, $tid, $pid, $def['solve']);
            } else {
                $this->fulfill_request($fid, $tid, $pid, $fulfilledBySeasonedSolver = false);
            }
            break;
        }
    }/*}}}*/

    public function handle_terminate_confirm($fid, $tid, $pid, $reqdata, $cfg)/*{{{*/
    {
        $confirm = (int) $this->request->variable('confirm', 0);
        if ($confirm) {
            $termination_reason = $this->request->variable('termination_reasons', '');
            if (!$termination_reason) {
                trigger_error('You must provide a reason for termination. Error Code: 2a9d306c8a');
            }
            $this->solve_request($fid, $tid, $pid, $this->def['terminate'], $reqdata, $termination_reason);
        }
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            switch ($reqdata['status']) {
            case $this->def['dib']:
                $status_strn = 'Accepted';
                break;
            case $this->def['fulfill']:
                $status_strn = 'Fulfilled';
                break;
            default:
                $status_strn = '';
            }
            $topic_data = $this->select_topic($tid);
            $username = '<span style="color: #' . $reqdata['fulfiller_colour'] . '; font-weight: 900;">' . $reqdata['fulfiller_username'] . '</span>';
            $this->template->assign_vars(
                [
                'FORUM_ID' => $fid,
                'TOPIC_ID' => $tid,
                'POST_ID' => $pid,
                'DEF_TERMINATE' => $this->def['terminate'],
                'DEF_SOLVE' => $this->def['solve'],
                'FULFILLER_USERNAME' => $username,
                'S_TOPIC_TITLE' => $topic_data['topic_title'],
                'S_STATUS' => $status_strn,
                ]
            );
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

    public function select_request_users($uid)/*{{{*/
    {
        $sql = 'SELECT * FROM ' . $this->tbl['requsr'] ." WHERE user_id=$uid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function update_request_users($uid, $data)/*{{{*/
    {
        $sql = 'UPDATE ' . $this->tbl['requsr'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE user_id=' . $uid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }/*}}}*/

    public function recalculate_request_users($uid)/*{{{*/
    {
        $cdef = $this->def['set']['closed'];
        $gid = $this->user->data['group_id'];
        $gdata = $this->select_group($gid);
        $sql = 'SELECT count(*) as n_use FROM ' . $this->tbl['req'] ." WHERE requester_uid=$uid AND " .
            $this->db->sql_in_set('status', $cdef, true);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $time = time();
        $thirtydays = 60*60*24*30;
        $reset_time = $time + $thirtydays;
        $n_use = $row['n_use'];
        $new_offset = $gdata['snp_req_n_offset'] + $gdata['snp_req_n_base'] - $n_use;
        $data = [
            'n_use'           => $gdata['snp_req_n_base'],
            'n_cycle_use'     => 0,
            'n_offset'        => $new_offset,
            'start_time'      => $time,
            'cycle_deltatime' => $thirtydays,
            'reset_time'      => $reset_time,
            'b_nolimit'       => $gdata['snp_req_b_nolimit'],
            'b_give'          => $gdata['snp_req_b_give'],
            'b_take'          => $gdata['snp_req_b_take'],
            'b_active'        => $gdata['snp_req_b_active'],
        ];
        $this->update_request_users($uid, $data);
        return;
    }/*}}}*/

    public function increment_request_users_slot($uid, $slot=1)/*{{{*/
    {
        $ru   = $this->select_request_users($uid);
        $udata = $this->select_user($ru['user_id']);
        if (!$ru || !$udata) {
            return;
        }
        $gdata = $this->select_group($udata['group_id']);
        $nu   = $ru['n_use'];
        $nb   = $gdata['snp_req_n_base'];
        $no   = $ru['n_offset'];
        $nu  -= $slot;
        if ($nu > $nb) {
            $no -= $nu - $nb;
            $nu = $nb;
        }
        if ($nu < 0) {
            $no -= $nu;
            $nu = 0;
        }
        $sql = 'UPDATE ' . $this->tbl['requsr'] . "
            SET n_use=$nu, n_offset=$no " . '
            WHERE user_id=' . $uid;
        $this->db->sql_query($sql);
    }/*}}}*/

    public function remove_request($tid)/*{{{*/
    {
        $data = ['tid' => $tid];
        $sql = 'DELETE FROM ' . $this->tbl['req'] . $this->db->sql_build_array('DELETE', $data);
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }/*}}}*/

    public function solve_request($fid, $tid, $pid, $new_status, $req, $termination_reason='')/*{{{*/
    {
        // Set confirmed_time
        $def = $this->def;
        $resolvedStatus = [$def['solve'], $def['terminate']];
        if (!$req) {
            meta_refresh(2, $this->u_action);
            trigger_error('That request does not exist. Error Code: d57e5d63b7');
        }
        if (in_array($req['status'], $resolvedStatus)) {
            meta_refresh(2, $this->u_action);
            trigger_error('That request had already been terminated.');
        }
        if ($new_status == $def['solve'] && $req['status'] != $def['fulfill']) {
            meta_refresh(2, $this->u_action);
            trigger_error('You can only solve request that has been fulfilled. Error Code: e4917d39fd');
        }
        $user_id = $this->user_id;
        $time = time();
        $data = [
            'status' => $new_status,
            'solved_time' => $time,
        ];
        // $b_op = $user_id == $req['requester_uid'];
        // $b_mod = $this->auth->acl_gets('a_', 'm_');
        // $gid = $this->user->data['group_id'];
        // $group_data = $this->select_group($gid);
        // $b_group = $group_data['snp_req_b_solve'];
        // if (!$b_op && !$b_mod && !$b_group) {
        if ($new_status == $def['terminate']) {
            if (!$this->isTrustedTerminator($req)) {
                //TODO: Send notification to user if closed by mod
                meta_refresh(2, $this->u_action);
                trigger_error('Only the requester and the moderators are allowed to close this request. Error Code: 585e9279e0');
            }
            $data['termination_reason'] = $termination_reason;
            $data['terminator_uid'] = $this->user_id;
        }
        $this->update_request($tid, $data);
        $this->increment_request_users_slot($req['requester_uid'], 1);
        $ptn = $this->ptn;
        $repl = '';
        if ($new_status == $def['solve']) {
            $repl = '[Solved]';
            $this->increment_user_request_solved($req);
        } elseif ($new_status == $def['terminate']) {
            $repl = '[Closed]';
        }
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status) {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        meta_refresh(2, $this->u_action);
        if ($new_status == $def['solve']) {
            trigger_error('This request was solved.');
        } elseif ($new_status == $def['terminate']) {
            $sql = 'UPDATE ' . $this->tbl['requsr'] . ' SET n_use_this_cycle=n_use_this_cycle+1 ' .
                " WHERE user_id={$user_id}";
            $this->db->sql_query($sql);
            trigger_error('This request was terminated.');
        }
    }/*}}}*/

    public function increment_user_request_solved($reqdata)/*{{{*/
    {
        $fulfiller_uid = $reqdata['fulfiller_uid'];
        $sql = 'UPDATE ' . USERS_TABLE . '
            SET snp_req_n_solve=snp_req_n_solve+1
            WHERE user_id=' . $fulfiller_uid;
        $this->db->sql_query($sql);
    }/*}}}*/

    public function rejectInvalidTerminator($reqdata)/*{{{*/
    {
        if (!$this->isTrustedTerminator($reqdata)) {
            trigger_error('Only the requester and the moderators have access to this page. Error Code: da49eb0a81');
        }
    }/*}}}*/

    public function isTrustedTerminator($reqdata)/*{{{*/
    {
        // This user is the original requester
        if ($this->user_id == $reqdata['requester_uid']) {
            return true;
        }
        if ($this->auth->acl_gets('a_', 'm_')) {
            return true;
        }
        // Belongs to solver group
        $gid = $this->user->data['group_id'];
        $group_data = $this->select_group($gid);
        if ($group_data['snp_req_b_solve']) {
            return true;
        }
        return false;
    }/*}}}*/

    public function rejectInvalidSolver($reqdata)/*{{{*/
    {
        if (!$this->isTrustedSolver($reqdata)) {
            trigger_error('Only the requester and the moderators have access to this page. Error Code: bfb6ca2540');
        }
    }/*}}}*/

    public function isTrustedSolver($reqdata)/*{{{*/
    {
        return $this->isTrustedTerminator($reqdata) || $this->isSeasonedSolver();
    }/*}}}*/

    public function isSeasonedSolver()/*{{{*/
    {
        // Solved enough requests to be trusted
        if ((int) $this->user->data['snp_req_n_solve'] >= $this::SEASONED_SOLVER_THRESHOLD) {
            return true;
        }
        return false;
    }/*}}}*/

    public function fulfill_request($fid, $tid, $pid, $fulfilledBySeasonedSolver=false)/*{{{*/
    {
        // Update titles on topic & posts also forum summary
        $ptn = $this->ptn;
        $repl = '[Fulfilled]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status) {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        $data = [
            'fulfilled_time' => time(),
            'status'         => 4,
        ];
        $this->update_request($tid, $data);
        // Send notification on fulfillment to requester
        $requestdata = $this->select_request($tid);
        $ruid = $requestdata['requester_uid'];
        $fuid = $requestdata['fulfiller_uid'];
        $requesterdata = $this->select_user($ruid);
        // If user doesn't want to receive notify skip this user
        if ($requesterdata['snp_enable_at_notify']) {
            $requester_id     = $requestdata['requester_uid'];
            $requester_name   = $requestdata['requester_username'];
            $requester_color  = $requestdata['requester_colour'];
            $fulfiller_id     = $requestdata['fulfiller_uid'];
            $fulfiller_name   = $requestdata['fulfiller_username'];
            $fulfiller_color  = $requestdata['fulfiller_colour'];
            $requester_string = get_username_string('no_profile', $requester_id, $requester_name, $requester_color);
            $fulfiller_string = get_username_string('no_profile', $fulfiller_id, $fulfiller_name, $fulfiller_color);
            if ($fulfilledBySeasonedSolver) {
                $text = $fulfiller_string . ' solved your request.<br>No further action is required.';
            } else {
                $text = $fulfiller_string . ' fulfilled your request<br>Please confirm request resolution.';
            }
            $type = 'request_fulfillment';
            $data = array(
                'user_id'      => $requester_id,
                'username'     => $requester_name,
                'post_id'      => $pid,
                'poster_id'    => $requester_id,
                'topic_id'     => $tid,
                'forum_id'     => $fid,
                'time'         => time(),
                'post_subject' => $text,
                'type'         => $type,
            );
            $this->notification->add_notifications(['jeb.snahp.notification.type.basic', ], $data);
        }
        $this->update_user($fuid, ['user_lastpost_time' => 0]);
        if (!$fulfilledBySeasonedSolver) {
            meta_refresh(2, $this->u_action);
            $message = 'Thank you for fulfilling this request!';
            trigger_error($message);
        }
    }/*}}}*/

    public function undib_request($fid, $tid, $pid)/*{{{*/
    {
        $dibdata = $this->select_dibs($tid);
        $def = $this->def;
        if (!$dibdata || !$dibdata['status']==$def['dib']) {
            trigger_error('No open dibs found.');
        }
        $b_dibber = $this->is_dibber($dibdata);
        $b_mod = $this->is_mod();
        if (!$b_dibber && !$b_mod) {
            trigger_error('Only dibber or moderators may undib.');
        }
        // Update titles on topic & posts also forum summary
        $ptn = $this->ptn;
        $repl = '[Request]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status) {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        $time = time();
        $data = [
            'fulfiller_uid'      => null,
            'fulfiller_username' => '',
            'fulfiller_colour'   => '',
            'commit_time'        => null,
            'status' => $this->def['open'],
        ];
        $this->update_request($tid, $data);
        $data = [
            'undib_time' => $time,
            'undibber_uid' => $this->user->data['user_id'],
            'status' => $this->def['undib'],
        ];
        $this->update_dibs($tid, $data);
        meta_refresh(2, $this->u_action);
        $message = 'You have undibbed this request.';
        trigger_error($message);
    }/*}}}*/

    public function dib_request($fid, $tid, $pid)/*{{{*/
    {
        if (!$this->user->data['snp_req_dib_enable']) {
            meta_refresh(2, $this->u_action);
            trigger_error('You are not allowed to dib requests.');
        }
        $dibdata = $this->select_dibs($tid);
        $def = $this->def;
        $time = time();
        if ($dibdata) {
            if ($dibdata['status'] == $def['dib']) {
                trigger_error('Someone already dibbed this request.');
            } else {
                // Must have been undibbed
                $b_dibber = $this->is_dibber($dibdata);
                if ($b_dibber && !$this->b_mod) {
                    if ($this->isSeasonedSolver()) {
                        $undib_cooldown = 21600;
                    } else {
                        $undib_cooldown = $this->config['snp_req_redib_cooldown_time'];
                    }
                    $redib_time = $dibdata['undib_time'] + $undib_cooldown;
                    if ($time < $redib_time) {
                        trigger_error(
                            'Cannot dib so soon after undibbing a request.<br>
                            You can dib this request again on '
                            . $this->get_dt($redib_time)
                        );
                    }
                }
            }
        }
        // Confirm self-db
        $userId = $this->user->data['user_id'];
        $topic_data = $this->select_topic($tid);
        if ($userId == $topic_data['topic_poster']) {
            $confirmation = $this->request->variable('confirm', '');
            if ($confirmation !== 'true') {
                $cfg = [
                    'tpl_name' => '@jeb_snahp/confirmation/component/generic/base.html',
                    'title' => 'Confirm Request Termination',
                ];
                $this->template->assign_vars(
                    [
                        'S_TITLE' => 'Are you sure?<br>You are about to call dibs on your OWN request',
                        'S_MESSAGE' => "You should do this only when you have found a new resource to your own request and will be posting that resource to the forum.",
                        'U_CONFIRM' => "/app.php/snahp/reqs/${fid}/${tid}/${pid}/{$this->def['dib']}/?confirm=true",
                        'U_CANCEL' => "/viewtopic.php?f=${fid}&t=${tid}",
                    ]
                );
                return $this->helper->render($cfg['tpl_name'], $cfg['title']);
            }
        }
        // Update titles on topic & posts also forum summary
        $ptn = $this->ptn;
        $repl = '[Accepted]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status) {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        $data = [
            'fulfiller_uid'      => $this->user->data['user_id'],
            'fulfiller_username' => $this->user->data['username'],
            'fulfiller_colour'   => $this->user->data['user_colour'],
            'commit_time'        => time(),
            'status' => $this->def['dib'],
        ];
        $this->update_request($tid, $data);
        $data = [
            'tid' => $tid,
            'dibber_uid' => $this->user->data['user_id'],
            'dib_time' => $time,
            'status' => $this->def['dib'],
        ];
        $this->insert_dibs($data);
        meta_refresh(2, $this->u_action);
        $message = 'You called dibs on a request! Thanks a lot for your help.';
        trigger_error($message);
    }/*}}}*/

    public function is_dibber($dibdata)/*{{{*/
    {
        return $this->user_id == $dibdata['dibber_uid'];
    }/*}}}*/

    public function is_requester($reqdata)/*{{{*/
    {
        return $this->user_id = $reqdata['requester_uid'];
    }/*}}}*/

    public function get_requests_as_json($username='')/*{{{*/
    {
        $this->reject_anon();
        if (!$username) {
            $user_id = $this->user->data['user_id'];
        } else {
            $this->reject_non_moderator();
            $userdata = $this->select_user_by_username($username);
            if (!$userdata) {
                return new JsonResponse([]);
            }
            $user_id = $userdata['user_id'];
        }
        $reqdata = $this->select_request_open_by_uid($user_id);
        $data = [];
        foreach ($reqdata as $entry) {
            $tid = $entry['tid'];
            $status = $entry['status'];
            $date = $this->user->format_date($entry['created_time']);
            switch ($status) {
            case 1:
                $status = 'Open';
                break;
            case 3:
                $status = 'Accepted';
                break;
            case 4:
                $status = 'Fulfilled';
                break;
            default:
                $status = 'Error';
                break;
            }
            $tdata = $this->select_topic($tid);
            $title = $tdata['topic_title'];
            $jsonentry = [
                't' => $tid,
                's' => $status,
                'd' => $date,
                'ti' => $title,
            ];
            $data[] = $jsonentry;
        }
        $json = new JsonResponse($data);
        return $json;
    }/*}}}*/
}
