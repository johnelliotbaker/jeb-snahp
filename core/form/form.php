<?php
namespace jeb\snahp\core\form;

class form
{
    protected $container;
    public function __construct(/*{{{*/
        $container
    ) {
        $this->container = $container;
        $this->config = $container->get('config');
        $this->db = $container->get('dbal.conn');
        $this->request = $container->get('request');
        $this->template = $container->get('template');
    }/*}}}*/

    public function test()/*{{{*/
    {
        prn($this->db->sql_escape('asdf'));
    }/*}}}*/

    public function set_config_var($data)/*{{{*/
    {
        $this->config->set($data['tpl_varname'], $data['value']);
    }/*}}}*/

    public function get_config_var($data)/*{{{*/
    {
        return $this->config[$data['tpl_varname']];
    }/*}}}*/

    public function get_manifest()/*{{{*/
    {
        $manifest = [
            [
                'varname' => 'gfksx_tfp_b_limit_cycle',
                'tpl_varname' => 'gfksx_tfp_b_limit_cycle',
                'func_set' => 'set_config_var',
                'func_get' => 'get_config_var',
                'default' => '1',
            ],
            [
                'varname' => 'snp_thanks_b_enable',
                'tpl_varname' => 'snp_thanks_b_enable',
                'func_set' => 'set_config_var',
                'func_get' => 'get_config_var',
                'default' => '1',
            ],
        ];
        return $manifest;
    }/*}}}*/

    public function process_manifest($manifest)/*{{{*/
    {
        foreach ($manifest as $job) {
            $varname = $job['varname'];
            $job['value'] = $this->request->variable((string)$varname, $job['default']);
            $this->{$job['func_set']}($job);
        }
        $data = [];
        return $data;
    }/*}}}*/

    public function set_template_vars_from_manifest($manifest)/*{{{*/
    {
        $tpl_vars = [];
        foreach ($manifest as $job) {
            $tpl_varname = strtoupper($job['tpl_varname']);
            $stored_value = $this->{$job['func_get']}($job);
            $tpl_vars[$tpl_varname] = $stored_value;
        }
        $this->template->assign_vars($tpl_vars);
    }/*}}}*/
}
