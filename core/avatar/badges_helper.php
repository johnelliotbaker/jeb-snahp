<?php
namespace jeb\snahp\core\avatar;

class badges_helper
{
    protected $container;
    protected $user_data = [];
    protected $post_data = [];
	public function __construct(
        $container
	)
	{/*{{{*/
        $this->container  = $container;
        $this->p['users'] = $container->getParameter('jeb.snahp.avatar.badge.users');
        $this->p['items'] = $container->getParameter('jeb.snahp.avatar.badge.items');
        $user = $container->get('user');
	}/*}}}*/

    public function process_badges($post_data, $options=[])/*{{{*/
    {
        $this->options = $options;
        $this->post_data = $post_data;
        $user_params = $this->setup_user($post_data['user_id']);
        $queue = $this->make_jobs($user_params);
        $res = $this->process_jobs($queue, $post_data, $user_params);
        return $res;
    }/*}}}*/

    public function setup_user($poster_id)/*{{{*/
    {
        $this->user_data = isset($this->p['users'][$poster_id]) ? $this->p['users'][$poster_id] : [];
        return $this->user_data;
    }/*}}}*/

    public function make_jobs($user_params)/*{{{*/
    {
        return isset($user_params['queue']) ? $user_params['queue'] : [];
    }/*}}}*/

    private function process_jobs($jobs, $post_data, $user_params)/*{{{*/
    {
        $res = [];
        foreach($jobs as $name)
        {
            $item_params = $this->p['items'][$name];
            $res[] = $this->process_job($name, $post_data, $user_params, $item_params);
        }
        return implode('', $res);
    }/*}}}*/

    private function process_job($jobname, $post_data, $user_params, $item_params)/*{{{*/
    {
        return $this->generate_named_html($jobname, $post_data, $user_params, $item_params);
    }/*}}}*/

    private function generate_named_html($jobname, $post_data, $user_params, $item_params)/*{{{*/
    {
        $job_data = $user_params['data'][$jobname];
        $type = isset($job_data['template_type']) ? $job_data['template_type'] : 'basic';
        $required_vars = $item_params['template'][$type]['fields'];
        $template_html = $item_params['template'][$type]['html'];
        $template_vars = $this->get_required_vars($required_vars, $post_data, $job_data, $item_params['data']);
        $html = $this->replace_template($template_html, $template_vars);
        return $html;
    }/*}}}*/

    private function get_required_vars($varnames, $post_data, $job_data, $item_data)/*{{{*/
    {
        $res = [];
        foreach ($varnames as $varname)
        {
            if (array_key_exists($varname, $post_data))
            {
                $res[$varname] = $post_data[$varname];
            }
            elseif (isset($job_data[$varname]))
            {
                $res[$varname] = $job_data[$varname];
            }
            elseif (isset($item_data[$varname]))
            {
                $res[$varname] = $item_data[$varname];
            }
            else
            {
                $res[$varname] = '';
            }
        }
        return $res;
    }/*}}}*/

    private function replace_template($strn, $vars)/*{{{*/
    {
        // https://stackoverflow.com/questions/7980741/efficient-way-to-replace-placeholders-with-variables
        $strn = preg_replace_callback('/\{([\.\w]+)}/', function ($match) use($vars){
            list ($_, $name) = $match;
            if (isset($vars[$name])) return $vars[$name];
        }, $strn);
        return $strn;
    }/*}}}*/

}
