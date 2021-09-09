<?php
namespace jeb\snahp\controller\switchboard;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum switchboard
 * */

class switchboard
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
    protected $input;
    public function __construct(
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth
    )
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
    }

    public function handle_commands()
    {
        $this->sauth->reject_non_dev('Error Code: f8c0b3bd3a');
        $command = $this->request->variable('command', '');
        switch($command)
        {
        case 'start_logging':
            $this->start_logging();
            break;
        case 'clear_log':
            $this->clear_log();
            break;
        case 'stop_logging':
            $this->stop_logging();
            break;
        default:
        }
        $cfg['tpl_name'] = '@jeb_snahp/switchboard/base.html';
        return $this->respond_query($cfg);
    }

    private function set_config_var($data)
    {
        prn("Setting config ${data['tpl_varname']} = ${data['value']}");
        $this->config->set($data['tpl_varname'], $data['value']);
    }

    private function get_config_var($data)
    {
        return $this->config[$data['tpl_varname']];
    }

    public function handle()
    {
        $this->sauth->reject_non_dev('Error Code: 381baba2ed');
        $cfg['tpl_name'] = '@jeb_snahp/switchboard/base.html';
        return $this->respond_query($cfg);
    }

    private function get_manifest()
    {
        $manifest = [
            [
                'varname' => 'snp_track_b_sup',
                'tpl_varname' => 'snp_track_b_sup',
                'func_set' => 'set_config_var',
                'func_get' => 'get_config_var',
                'default' => '1',
            ],
            [
                'varname' => 'snp_track_b_markread',
                'tpl_varname' => 'snp_track_b_markread',
                'func_set' => 'set_config_var',
                'func_get' => 'get_config_var',
                'default' => '1',
            ],
        ];
        return $manifest;
    }

    private function set_form_checkboxes($a_checkbox_data)
    {
        $data = [];
        foreach($a_checkbox_data as $key => $val)
        {
            $data[strtoupper($key)] = $val;
        }
        $this->template->assign_vars($data);
    }

    private function process_manifest($manifest)
    {
        foreach($manifest as $job)
        {
            $varname = $job['varname'];
            $job['value'] = $this->request->variable((string)$varname, $job['default']);
            $this->{$job['func_set']}($job);
        }
        $data = [];
        return $data;
    }

    private function set_template_vars_from_manifest($manifest)
    {
        $tpl_vars = [];
        foreach ($manifest as $job)
        {
            $tpl_varname = strtoupper($job['tpl_varname']);
            $stored_value = $this->{$job['func_get']}($job);
            $tpl_vars[$tpl_varname] = $stored_value;
            prn("Setting template ${tpl_varname} = ${stored_value}");
        }
        $this->template->assign_vars($tpl_vars);
    }

    private function respond_query($cfg)
    {
        $manifest = $this->get_manifest();
        $this->process_manifest($manifest);
        $this->set_template_vars_from_manifest($manifest);
        // $a_checkbox_data = $this->process_checkboxes($a_checkbox_name);
        // $this->set_form_checkboxes($a_checkbox_data);
        add_form_key('jeb_snp');
        if ($this->request->is_set_post('submit'))
        {
            if (!check_form_key('jeb_snp'))
            {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
        }
        $this->template->assign_vars([
            'SWITCHBOARD_STATEMENT' => 'asdf',
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Switchboard');
    }

}
