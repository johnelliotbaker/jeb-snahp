<?php
namespace jeb\snahp\Apps\MuteUser;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class MuteUserMCPController
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    protected $muteUserHelper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $muteUserHelper
    )/*{{{*/ {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->muteUserHelper = $muteUserHelper;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: e9610041bc');
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        $this->sauth->reject_non_dev('Error Code: 430236a547');
        switch ($mode) {
        case 'manage':
            $cfg['tpl_name'] = '@jeb_snahp/mute_user/base.html';
            $cfg['title'] = 'Mute a user';
            return $this->respondMuteUser($cfg);
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along. Error Code: b586a44968');
    }/*}}}*/

    private function _makePagination()
    {
        $baseUrl = $this->helper->route(
            'jeb_snahp_routing.mute_user',
            ['mode' => 'manage']
        );
        // Pagination
        $start = (int) $this->request->variable('start', '0');
        $total = $this->muteUserHelper->getMutedUserCount();
        $per_page = 10;
        $pg = new \jeb\snahp\core\pagination();
        $pagination = $pg->make($baseUrl, $total, $per_page, $start);
        $userList = $this->muteUserHelper->getMutedUserList($start, $per_page);
        $this->template->assign_vars(
            [
                'PAGINATION' => $pagination,
                'USERLIST' => $userList,
            ]
        );
        // Paginated data
    }

    private function _rejectIfUsernameIsEmpty($username)
    {
        if ($username=='') {
            trigger_error("Username cannot be empty. Error Code: e6dfc15f1d");
        }
    }

    private function _rejectIfUserNotFound($targetUserId)
    {
        if ($targetUserId==null) {
            trigger_error("User ${username} not found. Error Code: f17a220483");
        }
    }

    public function respondMuteUser($cfg)/*{{{*/
    {
        add_form_key('jeb/snahp');
        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('jeb/snahp')) {
                trigger_error('Form Key Error. Error Code: f8c2bedb35');
            }
            $username = $this->request->variable('username', '');
            $this->_rejectIfUsernameIsEmpty($username);
            $targetUserId = $this->sauth->userNameToUserId($username);
            $this->_rejectIfUserNotFound($targetUserId);
            $disableNewTopic = $this->request->variable(
                'disable_new_topic',
                'off'
            );
            $disablePostReply = $this->request->variable(
                'disable_post_reply',
                'off'
            );
            $disableNewTopic=='on'
                ? $this->muteUserHelper->muteUserNewTopic($targetUserId)
                : $this->muteUserHelper->unmuteUserNewTopic($targetUserId);
            $disablePostReply=='on'
                ? $this->muteUserHelper->muteUserPostReply($targetUserId)
                : $this->muteUserHelper->unmuteUserPostReply($targetUserId);
            $this->template->assign_vars(
                [
                    'DISABLE_NEW_TOPIC' => $disableNewTopic,
                    'DISABLE_POST_REPLY' => $disablePostReply,
                ]
            );
        }
        $action = $this->helper->route(
            'jeb_snahp_routing.mute_user',
            ['mode' => 'manage']
        );
        $this->_makePagination();
        $this->template->assign_vars([ 'ACTION' => $action, ]);
        return $this->helper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/
}
