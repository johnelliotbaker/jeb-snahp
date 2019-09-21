<?php
namespace jeb\snahp\controller\rh_tags;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class rh_tags_list
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
        $sauth
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
        $this->user_id = (int) $this->user->data['user_id'];
        $this->sauth->reject_non_dev('Error Code: 8a4bca81a2');
        $this->whitelist_cfg_name = 'robertheim_topictags_whitelist';
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        switch ($mode)
        {
        case 'whitelist':
            $cfg['tpl_name'] = '@jeb_snahp/rh_tags/component/whitelist/base.html';
            $cfg['title'] = 'RH Tags Whitelist';
            return $this->respond_whitelist($cfg);
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    private function convert_whitelist_for_config_text($whitelist)
    {
        $whitelist = explode(',', $whitelist);
        $whitelist = array_map(function($elem) {
            return '"' . $this->db->sql_escape(trim($elem)) . '"';
        }, $whitelist);
        $whitelist = array_filter($whitelist, function($elem) {
            return strlen($elem) > 2;
        });
        $whitelist = implode(',', $whitelist);
        $whitelist = "[${whitelist}]";
        return $whitelist;
    }

    public function respond_whitelist($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            add_form_key('jeb_snp');
            if ($this->request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $whitelist = $this->request->variable('whitelist', '');
                $whitelist = $this->convert_whitelist_for_config_text($whitelist);
                $this->config_text->set($this->whitelist_cfg_name, $whitelist);
            }
            $data = stripslashes($this->config_text->get($this->whitelist_cfg_name));
            if ($data)
            {
                if ($data[0]!='[' || $data[-1]!=']')
                {
                    trigger_error('Invalid RT Whitelist. Error Code: 694fe6cc77');
                }
                $data = explode(',', substr($data, 1, -1));
                $data = array_map(function($elem) {
                    if (preg_match('/"(.*?)"/', $elem, $match))
                    {
                        return $match[1];
                    }
                    return '';
                }, $data);
                $strn = implode(', ', $data);
            }
            $u_action = $this->helper->route('jeb_snahp_routing.rh_tags_list', ['mode' => 'whitelist']);
            $this->template->assign_vars([
                'RH_TAGS_WHITELIST' => $strn,
                'U_ACTION' => $u_action,
            ]);
            return $this->helper->render($cfg['tpl_name'], $cfg['title']);
        }
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

}
