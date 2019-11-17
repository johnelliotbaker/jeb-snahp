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
    protected $tags_helper;
    public function __construct(
        $db, $user, $config, $request, $template, $container, $helper, $config_text,
        $tbl,
        $sauth, $tags_helper
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
        $this->tags_helper = $tags_helper;
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
        case 'forum_map':
            $cfg['tpl_name'] = '@jeb_snahp/rh_tags/component/forum_map/base.html';
            $cfg['title'] = 'RH Tags Forum Map';
            return $this->respond_forum_map($cfg);
        case 'group_map':
            $cfg['tpl_name'] = '@jeb_snahp/rh_tags/component/group_map/base.html';
            $cfg['title'] = 'RH Tags Group Map';
            return $this->respond_group_map($cfg);
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }/*}}}*/

    private function comma_string_to_list($strn)/*{{{*/
    {
        $strn = preg_replace('#\s+#', '', $strn);
        return explode(',', $strn);
    }/*}}}*/

    private function number_string_to_list($forum_id_strn)/*{{{*/
    {
        $strn = preg_replace('#\s+#', '', $forum_id_strn);
        $strn = preg_replace('#\-+#', '-', $forum_id_strn);
        $strn = preg_replace_callback('#(\d+)\-(\d+)#', function($match) {
            if ($match[1] > $match[2]) return '';
            return implode(',', range($match[1], $match[2]));
        }, $strn);
        $arr = explode(',', $strn);
        return array_unique(array_filter($arr, function($arg) {return is_numeric($arg);}));
    }/*}}}*/

    public function respond_forum_map($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            add_form_key('jeb_snp');
            if ($this->request->is_set_post('add'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $forum_ids = $this->request->variable('forum_ids', '');
                $a_forum_id = $this->number_string_to_list($forum_ids);
                foreach($a_forum_id as $forum_id)
                {
                    $index = 0;
                    while(($tmp = $this->request->variable("groupname_{$index}", '')) && $index < 20)
                    {
                        $entry = [];
                        $entry['forum_id'] = $forum_id;
                        $entry['priority'] = $this->request->variable("priority_{$index}", 0);
                        $entry['groupname'] = $tmp;
                        $this->tags_helper->insert_or_update_forum_map($entry);
                        $index += 1;
                    }
                    $this->tags_helper->get_groupnames_from_forum_map($forum_id, 0);
                }
            }
            elseif ($this->request->is_set_post('delete'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $forum_ids = $this->request->variable('forum_ids', '');
                $a_forum_id = $this->number_string_to_list($forum_ids);
                foreach($a_forum_id as $forum_id)
                {
                    $this->tags_helper->delete_forum_map($forum_id);
                }
            }
            $u_action = $this->helper->route('jeb_snahp_routing.rh_tags_list', ['mode' => 'forum_map']);
            $tag_tree = $this->make_forum_tag_tree();
            $this->template->assign_vars([
                // 'RH_TAGS_FORUM_MAP' => $strn,
                'TAGTREE' => $tag_tree,
                'U_ACTION' => $u_action,
            ]);
            return $this->helper->render($cfg['tpl_name'], $cfg['title']);
        }
    }/*}}}*/

    public function respond_group_map($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            add_form_key('jeb_snp');
            if ($this->request->is_set_post('add'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                if ($groupname = $this->request->variable('groupname', ''))
                {
                    $index = 0;
                    while(($tmp = $this->request->variable("tagname_{$index}", '')) && $index < 20)
                    {
                        $entry = [];
                        $entry['groupname'] = $groupname;
                        $entry['tagname'] = $tmp;
                        $this->tags_helper->insert_or_update_group_map($entry);
                        $index += 1;
                    }
                    $this->tags_helper->get_tagnames_from_group_map($groupname, 0);
                }
            }
            elseif ($this->request->is_set_post('delete'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $groupname = $this->request->variable('groupname', '');
                $delete_queue = $this->request->variable('delete_queue', '');
                $tagnames = $this->comma_string_to_list($delete_queue);
                if ($groupname && $delete_queue)
                {
                    $this->tags_helper->delete_group_map($groupname, $tagnames);
                }
            }
            $group_tag_tree = $this->make_tag_group_tree();
            $u_action = $this->helper->route('jeb_snahp_routing.rh_tags_list', ['mode' => 'group_map']);
            $this->template->assign_vars([
                'TAGTREE' => $group_tag_tree,
                'U_ACTION' => $u_action,
            ]);
            return $this->helper->render($cfg['tpl_name'], $cfg['title']);
        }
    }/*}}}*/

    private function convert_whitelist_for_config_text($whitelist)/*{{{*/
    {
        $whitelist = explode(',', $whitelist);
        $whitelist = array_map(function($elem) {
            return '"' . $this->db->sql_escape(trim($elem)) . '"';
        }, $whitelist);
        $whitelist = array_filter($whitelist, function($elem) {
            return strlen($elem) > 2;
        });
        sort($whitelist);
        $whitelist = implode(',', $whitelist);
        $whitelist = "[${whitelist}]";
        return $whitelist;
    }/*}}}*/

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

    private function make_tag_group_tree()/*{{{*/
    {
        $cd = 3600;
        $sql_array = [
            'SELECT'	=> '*',
            'FROM'		=> [$this->tbl['tags_group_map'] => 'a'],
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $data = [];
        foreach($rowset as $row)
        {
            $groupname = $row['groupname'];
            $data[$groupname]['tagnames'][] = $row['tagname'];
        }
        return $data;
    }/*}}}*/

    private function make_forum_tag_tree()/*{{{*/
    {
        $cd = 3600;
        $fid_listings = $this->config['snp_fid_listings'];
        $a_forum_data = $this->select_subforum($fid_listings, $cd);
        $sql_array = [
            'SELECT'	=> 'a.groupname',
            'FROM'		=> [$this->tbl['tags_forum_map'] => 'a'],
        ];
        $data = [];
        foreach($a_forum_data as $forum_data)
        {
            $forum_id = $forum_data['forum_id'];
            $forum_name = $forum_data['forum_name'];
            $sql_array['WHERE'] = "a.forum_id=${forum_id}";
            $sql = $this->db->sql_build_query('SELECT', $sql_array);
            $result = $this->db->sql_query($sql);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            $arr = array_map(function($arg){return $arg['groupname'];}, $rowset);
            $data[$forum_id]['forumname'] = $forum_name;
            $data[$forum_id]['groupnames'] = $arr;
        }
        return $data;
    }/*}}}*/

    public function select_subforum($parent_id, $cooldown=0, $b_immediate=false)/*{{{*/
    {
        global $phpbb_root;
        include_once($phpbb_root . 'includes/functions_admin.php');
        $fid_listings = $this->config['snp_fid_listings'];
        $fid = get_forum_branch($fid_listings, 'children');
        $data = array_map(function($array){return ['forum_name' => $array['forum_name'], 'forum_id' => $array['forum_id']];}, $fid);
        return $data;
    }/*}}}*/

}
