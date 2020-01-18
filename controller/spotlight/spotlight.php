<?php
namespace jeb\snahp\controller\spotlight;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class spotlight
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
    protected $spotlight_helper;
    public function __construct(
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth, $spotlight_helper
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
        $this->spotlight_helper = $spotlight_helper;
        $this->user_id = $this->user->data['user_id'];
        $this->redirect_delay = 3;
        $this->redirect_delay_long = 6;
        // $this->u_manage = $this->helper->route('jeb_snahp_routing.spotlight', ['mode'=>'manage']);
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        $this->sauth->reject_anon();
        switch($mode)
        {
        case 'ls':
            return $this->respond_list_as_json();
        case 'clear':
            $this->sauth->reject_non_dev();
            return $this->respond_clear_as_json();
        case 'disable':
            $this->sauth->reject_non_dev();
            return $this->respond_disable_as_json();
        case 'enable':
            $this->sauth->reject_non_dev();
            return $this->respond_enable_as_json();
        case 'update':
            $this->sauth->reject_non_dev();
            return $this->respond_update_as_json();
        }
        trigger_error('Invalid mode. Error Code: 3015f74010');
    }/*}}}*/

    private function respond_disable_as_json()/*{{{*/
    {
      $this->config->set('snp_spot_b_master', 0);
      return new JsonResponse(['status' => 'disable']);
    }/*}}}*/

    private function respond_enable_as_json()/*{{{*/
    {
      $this->config->set('snp_spot_b_master', 1);
      return new JsonResponse(['status' => 'enable']);
    }/*}}}*/

    private function respond_list_as_json()/*{{{*/
    {
      if (!$this->config['snp_spot_b_master']) { return new JsonResponse([ 'status'=>'fail', 'reason' => 'Disabled. Error Code: 0659f95ce1' ]); }
      return new JsonResponse(json_decode(
        $this->spotlight_helper->get_cache_list(), true
      ));
    }/*}}}*/

    private function respond_update_as_json()/*{{{*/
    {
      $timeit0 = microtime(true);
      $this->sauth->reject_non_dev('Error Code: 2f9763b310');
      $this->spotlight_helper->update_list();
      $duration = microtime(true) - $timeit0;
      return new JsonResponse(['status' => 'update', 'duration' => $duration]);
    }/*}}}*/

    private function respond_clear_as_json()/*{{{*/
    {
      $this->sauth->reject_non_dev('Error Code: b55ce5000b');
      $this->spotlight_helper->clear_cache();
      return new JsonResponse(['status' => 'cleared']);
    }/*}}}*/

}
