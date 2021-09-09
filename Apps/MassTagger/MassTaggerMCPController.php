<?php
namespace jeb\snahp\Apps\MassTagger;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class MassTaggerMCPController
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
    protected $myHelper;
    protected $formHelper;
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
        $myHelper,
        $formHelper
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
        $this->myHelper = $myHelper;
        $this->formHelper = $formHelper;
        $this->userId = (int) $this->user->data['user_id'];
    }

    public function handle($mode)
    {
        $this->sauth->reject_non_dev('Error Code: d5dc0f421a');
        switch ($mode) {
        case 'main':
            $cfg['tpl_name'] = '@jeb_snahp/mass_tagger/base.html';
            $cfg['title'] = 'Mass Mover V3';
            return $this->respondMassTagger($cfg);
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along. Error Code: c8e92b5051');
    }

    public function setContext($varname, $var)
    {
        $this->template->assign_var($varname, $var);
    }

    public function getSelectedTags()
    {
        $varNames = $this->request->variable_names();
        $checkboxNames = array_filter(
            $varNames,
            function ($arg) {
                return preg_match('#^cb-#', $arg);
            }
        );
        return array_map(
            function ($arg) {
                return preg_replace('#^cb-#', '', $arg);
            },
            $checkboxNames
        );
    }

    public function respondMassTagger($cfg)
    {
        $required = ['search_terms'=>'', 'topic_ids'=>'', 'forum_id'=>1];
        $rv = $this->formHelper->getRequestVars($required);
        [$topicIds, $forumId, $searchTerms] = [
            $rv['topic_ids'], $rv['forum_id'], $rv['search_terms'], ];
        $topicIds = $this->myHelper->topicIdsStringToList($topicIds);
        $this->setContext('TAGS', $this->myHelper->getWhitelistTags());
        if ($this->request->is_set_post('search')) {
            $topics = $this->myHelper->topicsFromSearchTerms($searchTerms, $forumId);
            $topicIds = $this->myHelper->topicIdsFromTopics($topics);
        } elseif ($this->request->is_set_post('add')) {
            $selectedTags = $this->getSelectedTags();
            foreach ($topicIds as $topicId) {
                $this->myHelper->addTags($topicId, $selectedTags);
            }
        } elseif ($this->request->is_set_post('remove')) {
            $selectedTags = $this->getSelectedTags();
            foreach ($topicIds as $topicId) {
                $this->myHelper->removeTags($topicId, $selectedTags);
            }
        }
        $this->_embedSubforumSelector('FROM_FORUM', $forumId);
        $rv['action'] = $this->helper->route(
            'jeb_snahp_routing.mass_tagger.mcp',
            ['mode' => 'main']
        );
        $rv['topic_ids'] = implode(', ', $topicIds);
        $this->formHelper->setTemplateVars($rv);
        return $this->helper->render($cfg['tpl_name'], $cfg['title']);
    }

    private function _embedSubforumSelector($varname, $selectId)
    {
        $subforumSelectorHTML = $this->myHelper->makeSubforumSelectorHTML($selectId);
        $this->template->assign_var($varname, $subforumSelectorHTML);
    }
}
