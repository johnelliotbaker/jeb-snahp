<?php

namespace jeb\snahp\Apps\DeadLinks;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\JsonResponse;
use \R as R;

class AuthorView
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
        $this->sauth->reject_new_users('Error Code: 090a034eab');
    }/*}}}*/

    public function view()/*{{{*/
    {
        $data = $this->getReports();
        return new JsonResponse($data);
    }/*}}}*/


    public function getReports()
    {
        return $this->Entry->getOpenReports($this->sauth->userId);
    }
}
