<?php
namespace jeb\snahp\Apps\Xmas;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class XmasPrizeDistributorController
{
    protected $request;
    protected $sauth;
    protected $helper;
    public function __construct(
        $request,
        $sauth,
        $helper
    ) {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->sauth->reject_non_dev('Error Code: a5e8ee80c7');
    }/*}}}*/

    public function distributeInvites()/*{{{*/
    {
        $this->helper->distributeInvites($start);
        return new Response('Success');
    }/*}}}*/

    public function distribute($start)/*{{{*/
    {
        $this->helper->distribute($start);
        return new Response('Success');
    }/*}}}*/

    public function distributeQuestPrizes()/*{{{*/
    {
        $this->helper->distributeQuestPrizes();
        return new Response('Success');
    }/*}}}*/
}
