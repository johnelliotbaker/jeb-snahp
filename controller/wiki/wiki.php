<?php
namespace jeb\snahp\controller\wiki;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum Wiki
 * */

class wiki
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
    protected $parsedown;
    protected $basedir;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $parsedown
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->parsedown = $parsedown;
        $this->wikidir = 'ext/jeb/snahp/styles/all/template/wiki/';
        $this->datadir = $this->wikidir . 'data/';
        $this->templatedir = '@jeb_snahp/wiki/';
        $this->editordir = '@jeb_snahp/wiki/component/editor/';
        $this->user_id = $this->user->data['user_id'];
    }

    public function handle($mode)
    {
        $this->sauth->reject_anon();
        $cfg['tpl_name'] = $this->templatedir . 'base.html';
        $cfg['name'] = $mode;
        return $this->render($cfg);
    }

    public function handle_editor($mode)
    {
        $this->sauth->reject_user_not_in_groupset($this->user_id, 'Keepers');
        switch ($mode) {
        case 'edit':
            return $this->respond_editor();
        case 'delete':
            return $this->respond_delete_entry_as_json();
        default:
        trigger_error('Invalid mode. Error Code: c20870b167');
        }
    }

    private function respond_delete_entry_as_json()
    {
        $name = $this->request->variable('name', '');
        if (!$name) {
            return new JsonResponse(['status' => 0]);
        }
        $b_success = $this->delete_entry($name) ? 1 : 0;
        return new JsonResponse(['status' => $b_success]);
    }

    private function get_list()
    {
        $tbl = $this->tbl['wiki'];
        $sql = 'SELECT name FROM ' . $tbl;
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    private function delete_entry($name)
    {
        $tbl = $this->tbl['wiki'];
        $name = $this->db->sql_escape($name);
        $sql = 'DELETE FROM ' . $tbl . " WHERE name='${name}'";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }

    private function respond_editor()
    {
        $tbl = $this->tbl['wiki'];
        $tpl_name = $this->editordir . 'base.html';
        add_form_key('jeb_snp');
        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('jeb_snp')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            $text = $this->request->variable('wiki_editor_textarea', '');
            $name = $this->request->variable('wiki_editor_name', '');
            $b_public = $this->request->variable('wiki_editor_cb_public', 'off') === 'on' ? 1 : 0;
            if (!$name || !$text) {
                trigger_error('Invalid name or text. Error Code: f2692b4560');
            }
            $this->template->assign_vars([
                'WIKI_NAME' => $name,
                'WIKI_TEXT' => $text,
                'B_PUBLIC' => $b_public,
            ]);
            $this->add_or_edit_entry($name, $text, $b_public);
        } elseif ($this->request->is_set_post('load')) {
            if (!check_form_key('jeb_snp')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            $name = $this->request->variable('wiki_editor_name', '');
            if (!$name) {
                trigger_error('Invalid name or text. Error Code: ce6fbd713f');
            }
            if ($row = $this->select_entry($name)) {
                $text = $row['text'];
                $b_public = $row['b_public'];
                $this->template->assign_vars([
                    'WIKI_NAME' => $name,
                    'WIKI_TEXT' => $text,
                    'B_PUBLIC' => $b_public,
                ]);
            }
        } elseif ($this->request->is_set_post('delete')) {
            if (!check_form_key('jeb_snp')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            $name = $this->request->variable('wiki_editor_name', '');
            if (!$name) {
                trigger_error('Invalid name or text. Error Code: 87b2dd3a83');
            }
            $this->delete_entry($name);
        } else {
            $name = $this->request->variable('name', '');
            if ($row = $this->select_entry($name)) {
                $text = $row['text'];
                $b_public = $row['b_public'];
                $this->template->assign_vars([
                    'WIKI_NAME' => $name,
                    'WIKI_TEXT' => $text,
                    'B_PUBLIC' => $b_public,
                ]);
            }
        }
        $wiki_names = $this->get_list();
        foreach ($wiki_names as $name) {
            $this->template->assign_block_vars('WIKI_NAMES', $name);
        }
        return $this->helper->render($tpl_name, 'Wikipedia Editor');
    }

    private function add_or_edit_entry($name, $text, $b_public=0)
    {
        $time = time();
        $tbl = $this->tbl['wiki'];
        $name = $this->db->sql_escape($name);
        $sql = 'SELECT * FROM ' . $tbl . " WHERE name='${name}'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $b_exist = isset($row) && !empty($row);
        if ($b_exist) {
            $created_time = $row['created_time'];
        } else {
            $created_time = $time;
        }
        $editor_id = $this->user_id;
        $edited_time = $time;
        $data = [
            'editor_id' => $editor_id,
            'name' => $name,
            'text' => $text,
            'edited_time' => $edited_time,
            'created_time' => $created_time,
            'b_public' => $b_public,
        ];
        if ($b_exist) {
            $sql = 'UPDATE ' . $tbl . '
                SET '. $this->db->sql_build_array('UPDATE', $data) . "
                WHERE name='${name}'";
            $this->db->sql_query($sql);
        } else {
            $sql = 'INSERT INTO ' . $tbl .
                $this->db->sql_build_array('INSERT', $data);
            $this->db->sql_query($sql);
        }
        return $this->db->sql_affectedrows() > 0;
    }

    private function select_entry($name)
    {
        $tbl = $this->tbl['wiki'];
        $name = $this->db->sql_escape($name);
        // $sql = 'SELECT * FROM ' . $tbl . " WHERE name='${name}'";
        $where = "name='${name}'";
        $sql_array = [
            'SELECT'	=> 'a.*, b.username, b.user_colour',
            'FROM'		=> [$tbl => 'a'],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [USERS_TABLE => 'b'],
                    'ON'	=> 'a.editor_id=b.user_id',
                ],
            ],
            'WHERE'		=> $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $b_exist = isset($row) && !empty($row);
        if (!$b_exist) {
            return '';
        }
        $row['text'] = htmlspecialchars_decode($row['text']);
        return $row;
    }

    private function select_nav()
    {
        $navigation_name = 'snp_wiki_nav_json';
        return $this->select_entry($navigation_name);
    }

    private function get_nav_text()
    {
        $row = $this->select_nav();
        return isset($row['text']) ? $row['text'] : '';
    }

    private function make_nav_parent_elem_before($entry)
    {
        $uuid = uniqid('nav_');
        return '<li>
              <a href="#' . $uuid .'" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">' . $entry . '</a>
              <ul class="collapse list-unstyled" id="' . $uuid . '">';
    }

    private function make_nav_parent_elem_after($entry)
    {
        return '</ul></li>';
    }

    private function make_nav_leaf_elem($entry)
    {
        $name = $entry['name'];
        $link = $entry['link'];
        return "<li><a href='${link}'>${name}</a></li>"  ;
    }

    private function make_nav_html()
    {
        $text = $this->get_nav_text();
        $res = json_decode($text, true);
        if (!$navdata = $res['navigation']) {
            return '';
        };
        $res = [];
        foreach ($navdata as $entry) {
            $res[] = $this->parse_navigation_entry($entry);
        }
        return implode(PHP_EOL, $res);
    }

    private function parse_navigation_entry($entry)
    {
        if (isset($entry['private']) && $entry['private'] && !$this->b_keepers) {
            return '';
        }
        if (isset($entry['children']) && !empty($entry['children'])) {
            $children = $entry['children'];
            $tmp = [];
            $tmp[] = $this->make_nav_parent_elem_before($entry['name']);
            foreach ($children as $child) {
                $tmp[] = $this->parse_navigation_entry($child);
            }
            $tmp[] = $this->make_nav_parent_elem_after($entry['name']);
            $strn = implode(PHP_EOL, $tmp);
        } else {
            $strn = $this->make_nav_leaf_elem($entry);
        }
        return $strn;
    }

    private function render($cfg)
    {
        $name = $cfg['name'];
        $row = $this->select_entry($name);
        if (!$row) {
            trigger_error("Wikipedia article for <b>${name}</b> does not exist. Error Code: 23a75a95b0");
        }
        $this->b_keepers = $this->sauth->user_belongs_to_groupset($this->user_id, 'Keepers');
        $navigation_html = $this->make_nav_html();
        $b_public = $row['b_public'];
        $text = $row['text'];
        $b_hidden = false;
        if (!$b_public) {
            if (!$this->b_keepers) {
                trigger_error('You do not have the permission to view this wikipedia article. Error Code: 9888c98c71');
            }
            $b_hidden = true;
        }
        $html = $this->parsedown->text($text);
        $this->template->assign_vars([
            'HTML' => $html,
            'NAME' => $name,
            'B_PUBLIC' => $b_public,
            'B_KEEPERS' => $this->b_keepers,
            'B_HIDDEN' => $b_hidden,
            'NAVIGATION' => $navigation_html,
            'EDITOR_ID' => $row['editor_id'],
            'EDITOR_USERNAME' => $row['username'],
            'EDITOR_USER_COLOUR' => $row['user_colour'],
            'EDIT_LOCAL_TIME' => $this->user->format_date($row['edited_time']),
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Wikipedia');
    }
}
