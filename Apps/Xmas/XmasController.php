<?php
namespace jeb\snahp\Apps\Xmas;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class XmasController
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $phpHelper;
    protected $tbl;
    protected $sauth;
    protected $helper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $phpHelper,
        $tbl,
        $sauth,
        $helper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->phpHelper = $phpHelper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }

    public function resetTimer($mode)
    {
        $this->sauth->reject_non_dev();
        $data = $this->helper->resetTimer($mode);
        return new Response(json_encode($data));
    }

    public function summary()
    {
        $data = $this->helper->summary();
        return new JsonResponse($data);
    }

    public function score()
    {
        $this->sauth->reject_non_dev();
        $data = $this->helper->getScoreDistribution();
        return new JsonResponse($data);
    }

    public function simulateCreate()
    {
        $this->sauth->reject_non_dev();
        $maxUser = 10000;
        $startTime = microtime(true);
        $count = 0;
        [$start, $end] = [60, $maxUser];
        $users = array_merge([1], range($start, $end));
        foreach ($users as $userId) {
            $count += 1;
            $this->helper->createBoard($userId);
        }
        $elapsed = microtime(true) - $startTime;
        print_r("Created {$count} accounts in $elapsed seconds<br>");
    }

    public function simulateVotes()
    {
        $this->sauth->reject_non_dev();
        $this->helper->simulateVoting();
    }

    public function simulateVotesPeriod($period)
    {
        $this->sauth->reject_non_dev();
        $this->helper->simulateVoting($period);
    }
}
