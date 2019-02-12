<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\event;


use jeb\snahp\core\base;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * snahp Event listener.
 */
class mod_listener extends base implements EventSubscriberInterface
{

    public function __construct()
    {
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => [
                ['setup_moderator', 0],
            ],
            'core.viewtopic_assign_template_vars_before'  => array(
                array('insert_move_topic_button', 0),
            ),
            'core.viewtopic_modify_post_row'  => array(
                array('Show_report_details_in_post', 0),
            ),
        );
    }

    public function is_mod()
    {
        return $this->auth->acl_gets('a_', 'm_');
    }

    public function modify_session()
    {
        $fid['mcp_move_to'] = 1;
        $last_visit = $this->user->data['user_lastvisit'];
        $this->user->set_cookie('fid', tracking_serialize($fid), $last_visit + 31536000);
        $this->request->overwrite($this->config['cookie_name'] . '_fid', tracking_serialize($fid), \phpbb\request\request_interface::COOKIE);
    }

    public function check_session()
    {
        $tracking = $this->request->variable($this->config['cookie_name'] . '_fid', '', true, \phpbb\request\request_interface::COOKIE);
        $tracking = ($tracking) ? tracking_unserialize($tracking) : array();
        prn($tracking);
    }

    public function setup_moderator($event)
    {
        if (!$this->is_mod()) return false;
        $this->template->assign_vars([
            'B_MOD' => true,
        ]);
    }

    public function Show_report_details_in_post($event)
    {
        $post_row = $event['post_row'];
        $message = $post_row['MESSAGE'];
        $b_report = $post_row['S_POST_REPORTED'];
        if ($b_report)
        {
            $post_id = $post_row['POST_ID'];
            $report_id = 0;
            $sql_ary = array(
                'SELECT'    => 'r.post_id, r.user_id, r.report_id, r.report_closed, report_time, r.report_text, r.reported_post_text, r.reported_post_uid, r.reported_post_bitfield, r.reported_post_enable_magic_url, r.reported_post_enable_smilies, r.reported_post_enable_bbcode, rr.reason_title, rr.reason_description, u.username, u.username_clean, u.user_colour',
                'FROM'        => array(
                    REPORTS_TABLE            => 'r',
                    REPORTS_REASONS_TABLE    => 'rr',
                    USERS_TABLE                => 'u',
                ),
                'WHERE'        => (($report_id) ? 'r.report_id = ' . $report_id : "r.post_id = $post_id") . '
                AND rr.reason_id = r.reason_id
                AND r.user_id = u.user_id
                AND r.pm_id = 0',
            'ORDER_BY'    => 'report_closed ASC',
            );
            $sql = $this->db->sql_build_query('SELECT', $sql_ary);
            $result = $this->db->sql_query_limit($sql, 1);
            $report = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            $reason_title = $report['reason_title'];
            $reason_description = $report['reason_description'];
            $report_time = $this->user->format_date($report['report_time']);
            $report_text = $report['report_text'];
            $report_id = $report['report_id'];
            $username = $report['username'];
            $reporter_id = $report['user_id'];
            $user_colour = $report['user_colour'];
            $strn = '
<div id="cp-main" class="cp-main mcp-main panel-container" style="width:100%;">
<div id="report" class="panel">
  <div class="inner">
    <div class="postbody">
      <h3>Report reason: '.$reason_title.'</h3>
      <p class="author">Reported by 
        <a href="./memberlist.php?mode=viewprofile&amp;u='.$reporter_id.'"
           style="color: ' . $user_colour . '#AA0000;" class="username-coloured">' . $username . '</a>
        « ' . $report_time . '</p>
      <div class="content">' .
"<label>Report Description:</label>" .
"<p>$reason_description</p>" . 
"<label>Message from the reporter:</label>" .
"<p>$report_text</p>" .
      '</div>
    </div>
  </div>
</div>
<form method="post" id="mcp_report" action="./mcp.php?i=reports&mode=report_details&p='. $post_id . '">
<fieldset class="submit-buttons">
<input class="button1" type="submit" value="Close report" name="action[close]">
<input class="button2" type="submit" value="Delete report" name="action[delete]">
<input type="hidden" name="report_id_list[]" value="'. $report_id . '">
</fieldset>
</form>
</div>
';
            $post_row['MESSAGE'] = $strn . PHP_EOL . $message;
            $event['post_row'] = $post_row;
        }
    }

    public function insert_move_topic_button($event)
    {
        // For use with: viewtopic_dropdown_top_custom.html
        if (!$this->is_mod()) return false;
        include_once('includes/functions_admin.php');
        $select_id = $this->get_cookie('mcp_move_topic_to_fid');
        $select_id = $select_id ? $select_id : false;
        $forum_select = make_forum_select($select_id = $select_id, $ignore_id = false, $ignore_acl = false, $ignore_nonpost = false, $ignore_emptycat = true, $only_acl_post = false, $return_array = false);
        $this->template->assign_vars([
            'S_FORUM_SELECT' => $forum_select,
            'B_MOD_MOVE_TOPIC' => true,
        ]);
    }

}
