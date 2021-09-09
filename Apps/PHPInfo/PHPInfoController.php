<?php
namespace jeb\snahp\Apps\PHPInfo;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class PHPInfoController
{
    protected $sauth;
    public function __construct(
        $sauth
    ) {
        $this->sauth = $sauth;
        $this->sauth->reject_non_dev('Error Code: 93b1b667fc');
    }

    public function view()
    {
        phpinfo();
        return new Response();
    }
}
