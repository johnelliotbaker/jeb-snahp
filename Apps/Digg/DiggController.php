<?php
namespace jeb\snahp\Apps\Digg;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class DiggController
{
    protected $sauth;
    protected $helper;
    protected $phpbbHelper;
    public function __construct($request, $template, $phpbbHelper, $pagination, $sauth, $helper)
    {
        $this->request = $request;
        $this->template = $template;
        $this->phpbbHelper = $phpbbHelper;
        $this->pagination = $pagination;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = (int) $this->sauth->userId;
        $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }/*}}}*/

    public function viewSubscriptions()/*{{{*/
    {
        $userId = (int) $this->userId;
        $offset = $this->request->variable('offset', 0);
        $pageSize = $this->request->variable('limit', 20);
        $results = $this->helper->getUserDiggSlave($userId);
        $this->pagination->generate_template_pagination(
            $baseUrl = "/snahp/digg/subscriptions?limit=${pageSize}",
            $blockVarName = 'pagination',
            $startName = 'offset',
            $numItems = $results['count'],
            $perPage = $pageSize,
            $start = $offset
        );
        foreach ($results['results'] as $row) {
            $tid = $row['topic_id'];
            $u_topic = "/viewtopic.php?t=$tid";
            $broadcast_time = $row['broadcast_time'];
            $broadcast_time = $broadcast_time < 1 ? "Never" : $this->sauth->user->format_date($broadcast_time);
            $group = array(
                'TOPIC_TITLE' => $row['topic_title'],
                'BROADCAST_TIME' => $broadcast_time,
                'U_TOPIC' => $u_topic,
            );
            $this->template->assign_block_vars('postrow', $group);
        }
        $this->template->assign_var('IS_SUBSCRIPTION_PAGE', true);
        $cfg['tpl_name'] = '@jeb_snahp/digg/component/mydigg/base.html';
        $cfg['title'] = 'My Diggs';
        return $this->phpbbHelper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/

    public function viewBroadcasts()/*{{{*/
    {
        $userId = (int) $this->userId;
        $offset = $this->request->variable('offset', 0);
        $pageSize = $this->request->variable('limit', 20);
        $results = $this->helper->getUserDiggMaster($userId);
        $this->pagination->generate_template_pagination(
            $baseUrl = "/snahp/digg/broadcasts?limit=${pageSize}",
            $blockVarName = 'pagination',
            $startName = 'offset',
            $numItems = $results['count'],
            $perPage = $pageSize,
            $start = $offset
        );
        foreach ($results['results'] as $row) {
            $tid = $row['topic_id'];
            $u_topic = "/viewtopic.php?t=$tid";
            $broadcast_time = $row['broadcast_time'];
            $broadcast_time = $broadcast_time < 1 ? "Never" : $this->sauth->user->format_date($broadcast_time);
            $group = array(
                'TOPIC_TITLE' => $row['topic_title'],
                'BROADCAST_TIME' => $broadcast_time,
                'U_TOPIC' => $u_topic,
            );
            $this->template->assign_block_vars('postrow', $group);
        }
        $this->template->assign_var('IS_BROADCAST_PAGE', true);
        $cfg['tpl_name'] = '@jeb_snahp/digg/component/mydigg/base.html';
        $cfg['title'] = 'My Diggs';
        return $this->phpbbHelper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/

    public function apiMyDigg()/*{{{*/
    {
        $userId = (int) $this->userId;
        $results = $this->helper->getUserDiggMaster($userId);
        $resp = new JsonResponse($results);
        $resp->setEncodingOptions(JSON_NUMERIC_CHECK);
        return $resp;
    }/*}}}*/
}
