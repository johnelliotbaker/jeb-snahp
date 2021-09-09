<?php
namespace jeb\snahp\Apps\Snowfall;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class SnowfallController
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

    public function reset()
    {
        $this->sauth->reject_non_dev('Error Code: ada9625f21');
        $this->helper->reset();
        return new JsonResponse([]);
    }

    public function activate()
    {
        $this->helper->activate();
        return new JsonResponse([]);
    }

    public function changeText()
    {
        $text = $this->request->variable('text', '');
        $success = $this->helper->changeText($text);
        $statusCode = $success ? 200 : 400;
        return new JsonResponse(['text' => $text], $statusCode);
    }

    public function changeColor($color)
    {
        $success = $this->helper->changeColor($color);
        $statusCode = $success ? 200 : 400;
        return new JsonResponse($resp, $statusCode);
    }
}
