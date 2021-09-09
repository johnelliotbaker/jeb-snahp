<?php

namespace jeb\snahp\Apps\DeadLinks;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\JsonResponse;
use \R as R;

class ModeratorView
{
    protected $db;
    protected $request;
    protected $sauth;
    protected $Entry;
    public function __construct($db, $request, $sauth, $Entry)
    {
        $this->db = $db;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->Entry = $Entry;
        $this->sauth->reject_non_dev('Error Code: 137b8e6393');
    }

    public function view()
    {
        $data = $this->Entry->getOpenRequests();
        return new JsonResponse($data);
    }
}
