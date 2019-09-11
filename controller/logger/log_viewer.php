<?php
namespace jeb\snahp\controller\logger;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class log_viewer
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $config_text;
    protected $sauth;
    protected $product_class;
    protected $tbl;
    protected $logger;
    public function __construct(
        $db, $user, $config, $request, $template, $container, $helper, $config_text,
        $tbl,
        $sauth, $logger
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->config_text = $config_text;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->logger = $logger;
        $this->user_id = (int) $this->user->data['user_id'];
        $this->sauth->reject_non_dev('Error Code: 8a4bca81a2');
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        switch ($mode)
        {
        case 'view':
            $cfg['tpl_name'] = '@jeb_snahp/logger/viewer/base.html';
            $cfg['title'] = 'Log Viewer';
            return $this->respond_view($cfg);
        case 'is_log':
            return $this->respond_is_log_as_json();
        case 'enable':
            return $this->set_enable_as_json();
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    public function set_enable_as_json()/*{{{*/
    {
        $type = $this->request->variable('type', '');
        $b = $this->request->variable('val', 0);
        switch ($type)
        {
        case 'master':
            $this->config->set('snp_log_b_master', $b);
            return new JsonResponse(['status' => $this->config['snp_log_b_master']]);
        case 'posting':
            $this->config->set('snp_log_b_posting', $b);
            return new JsonResponse(['status' => $this->config['snp_log_b_posting']]);
        case 'viewtopic':
            $this->config->set('snp_log_b_viewtopic', $b);
            return new JsonResponse(['status' => $this->config['snp_log_b_viewtopic']]);
        }
    }/*}}}*/

    public function respond_is_log_as_json()/*{{{*/
    {
        $type = $this->request->variable('type', '');
        switch ($type)
        {
        case 'master':
            return new JsonResponse(['status' => $this->config['snp_log_b_master']]);
        case 'posting':
            return new JsonResponse(['status' => $this->config['snp_log_b_posting']]);
        case 'viewtopic':
            return new JsonResponse(['status' => $this->config['snp_log_b_viewtopic']]);
        }
        return new JsonResponse(['status' => 0]);
    }/*}}}*/

    public function respond_view($cfg)/*{{{*/
    {
        $type = $this->request->variable('type', 'posting');
        $data = $this->logger->get_log_by_type($type);
        for($i=0; $i<count($data); $i++)
        {
            $curr = $data[$i];
            $time = $curr['created_time'] / 1000000;
            $curr['datetime'] = $this->user->format_date($time);
            $curr['time'] = $time;
            $data[$i] = $curr;
        }
        for($i=0; $i<count($data)-1; $i++)
        {
            $nexx = $data[$i+1];
            $curr = $data[$i];
            $timedelta = $curr['time'] - $nexx['time'];
            $timedelta = sprintf('%0.4f', $timedelta);
            $curr['timedelta'] = $timedelta;
            $curr['time'] = sprintf('%0.1f', $curr['time']);
            $this->template->assign_block_vars('LOG', $curr);
        }
        return $this->helper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/

}
