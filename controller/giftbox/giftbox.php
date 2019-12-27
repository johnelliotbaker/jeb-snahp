<?php
namespace jeb\snahp\controller\giftbox;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum giftbox
 * */

class giftbox
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
    protected $giftbox_helper;
    public function __construct(
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth, $giftbox_helper
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->giftbox_helper = $giftbox_helper;
        $this->user_id = $this->user->data['user_id'];
        $this->redirect_delay = 3;
        $this->redirect_delay_long = 6;
        $this->u_manage = $this->helper->route('jeb_snahp_routing.giftbox', ['mode'=>'manage']);
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        $this->sauth->reject_anon();
        switch($mode)
        {
        case 'unwrap_status':
            return $this->respond_unwrap_status_as_json();
        case 'history':
            return $this->respond_history_as_json();
        case 'unwrap':
            return $this->respond_unwrap_as_json();
        case 'giveaway':
            if(!$this->sauth->is_dev()) trigger_error('Error Code: a445f32ba5');
            return $this->respond_giveaway_as_stream();
        case 'simulate':
            if(!$this->sauth->is_only_dev()) trigger_error('Error Code: 4a18be0eae');
            return $this->respond_simulate_as_stream();
        case 'set_cycle_time':
            $this->sauth->reject_non_dev();
            return $this->respond_set_cycle_time_as_json();
        }
        trigger_error('Invalid mode. Error Code: 39319143d2');
    }/*}}}*/

    private function respond_giveaway_as_stream()/*{{{*/
    {
        $simulate = $this->request->variable('simulate', 1);
        $this->giftbox_helper->manual_giveaway($simulate);
        return new JsonResponse([]);
    }/*}}}*/

    private function respond_simulate_as_stream()/*{{{*/
    {
        $n = $this->request->variable('n', 100000);
        $item_def = $this->giftbox_helper->simulate($n);
        return new JsonResponse([]);
    }/*}}}*/

    private function respond_history_as_json()/*{{{*/
    {
        $user_id = (int) $this->user_id;
        if ($user_id < 1)
        {
            return new JsonResponse(['status' => 'failure', 'history' => []]);
        }
        $history = array_reverse($this->giftbox_helper->get_user_history());
        $data = [
            'status' => 'success',
            'history' => $history
        ];
        return new JsonResponse($data);
    }/*}}}*/

    private function respond_unwrap_status_as_json()/*{{{*/
    {
        [$status, $time_left] = $this->giftbox_helper->get_unwrap_status($this->user_id);
        $data = [
            'status' => $status,
            'time_left' => $time_left
        ];
        return new JsonResponse($data);
    }/*}}}*/

    private function respond_unwrap_as_json()/*{{{*/
    {
        $item_def = $this->giftbox_helper->give_random_item();
        $data = [
            'status' => 'success',
            'item' => $item_def
        ];
        return new JsonResponse($data);
    }/*}}}*/

    private function respond_set_cycle_time_as_json()/*{{{*/
    {
        $cycle_time = $this->request->variable('cycle_time', 86400);
        $this->giftbox_helper->set_cycle_time($cycle_time);
        $data = ['status' => 'success'];
        return new JsonResponse($data);
    }/*}}}*/

}
