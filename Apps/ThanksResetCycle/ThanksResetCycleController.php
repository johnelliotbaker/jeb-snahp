<?php
namespace jeb\snahp\Apps\ThanksResetCycle;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\Apps\ThanksResetCycle\ThanksResetCycleHelper;

class ThanksResetCycleController
{
    const SUCCESS = 'SUCCESS';
    const FAILURE = 'FAILURE';
    const DEFAULT_RESET_TOKEN_COST = 1;
    const STAFF_RESET_TOKEN_COST = 0;
    const DEBUG = true;

    protected $db;/*{{{*/
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    protected $myHelper;
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
        $myHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->myHelper = $myHelper;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: 8efd88b110');
    }/*}}}*/

    public function overview()/*{{{*/
    {
        $cfg['tpl_name'] = '@jeb_snahp/thanks_reset_cycle/base.html';
        $cfg['title'] = 'Thanks Overview';
        $data = ['user_id' => $this->userId];
        $this->template->assign_var('DATA', $data);
        return $this->helper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/

    public function respondResetThanksCycle($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $isDev = $this->sauth->is_dev();
        $allowed = $userId===$this->userId || $isDev || $this::DEBUG;
        if (!$allowed) {
            trigger_error("Unauthorized access. Error Code: f65aa1c8d4");
        }
        if (!$this->myHelper->hasGivenAllAvailableThanks($userId)) {
            $message = 'You can still give thanks. Error Code: 9a0ae0ab6e';
            return new JsonResponse(
                ['status' => $this::FAILURE, 'message' => $message,]
            );
        }
        $tokenCost = $isDev
            ? $this::STAFF_RESET_TOKEN_COST
            : $this::DEFAULT_RESET_TOKEN_COST;
        if (!$this->myHelper->hasRequiredTokens($userId, $tokenCost)) {
            return new JsonResponse(
                [
                    'status' => $this::FAILURE,
                    'message' => "Not enough tokens. Error Code: 2f0b38179e",
                ]
            );
        }
        $success = $this->myHelper->resetUserTimestamps($userId);
        if ($success) {
            $this->myHelper->reduceThanksToken($userId, $tokenCost);
            $message = 'Your thanks limit has been reset.';
        } else {
            $message = 'Could not reduce token. Error Code: 5d32d0a73f';
        }
        $success = (bool) $success ? $this::SUCCESS : $this::FAILURE;
        return new JsonResponse(['status' => $success, 'message' => $message,]);
    }/*}}}*/

    public function respondRecentThanksList($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $isDev = $this->sauth->is_dev();
        $allowed = $userId===$this->userId || $isDev;
        if (!$allowed) {
            trigger_error('Unauthorized access. Error Code: 1cac651cce');
        }
        $thanks = $this->myHelper->getRecentThanks($userId);
        $tokens = $this->myHelper->getThanksResetTokens($userId);
        return new JsonResponse(
            ['status' => $this::SUCCESS, 'thanks' => $thanks, 'tokens' => $tokens]
        );
    }/*}}}*/
}
