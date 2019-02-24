<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

class thanks extends base
{

    public function __construct(
    )
    {
        // $this->u_action = 'app.php/snahp/admin/';
    }

    public function handle($mode)
    {
        switch($mode)
        {
        case 'datn':
            $cfg = [];
            return $this->delete_all_thanks_notifications($cfg);
        default:
            break;
        }
        trigger_error('You must provide a valid mode.');
    }

    public function delete_all_thanks_notifications($cfg)
    {
        $this->reject_anon();
        $this->delete_thanks_notifications();
        $js = new JsonResponse(['status' => 'success']);
        return $js;
    }

}
