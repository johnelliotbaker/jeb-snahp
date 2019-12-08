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
        // if ($this->sauth->user_belongs_to_groupset($this->user_id, 'Basic'))
        // {
        //     meta_refresh($this->redirect_delay_long, '/');
        //     trigger_error("You do not have permission to view this page. Error Code: 805345a533. Redirecting in {$this->redirect_delay_long} seconds ...");
        // }
        switch($mode)
        {
        case 'unwrap_status':
            return $this->respond_unwrap_status_as_json();
        case 'unwrap':
            return $this->respond_unwrap_as_json();
        case 'simulate':
            return $this->respond_simulate_as_stream();
        case 'set_cycle_time':
            return $this->respond_set_cycle_time_as_json();
        }
        trigger_error('Invalid mode. Error Code: 39319143d2');
    }/*}}}*/

    private function respond_simulate_as_stream()/*{{{*/
    {
        $n = $this->request->variable('n', 100000);
        $item_def = $this->giftbox_helper->simulate($n);
        return new JsonResponse([]);
    }/*}}}*/

    private function respond_unwrap_status_as_json()/*{{{*/
    {
        [$b, $time_left] = $this->giftbox_helper->can_unwrap($this->user_id);
        $data = [
            'status' => $b ? 'closed' : 'not_ready',
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
