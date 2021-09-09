<?php
namespace jeb\snahp\Apps\UserBlock;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class UserBlockController
{
    public function __construct($request, $sauth, $helper)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = (int) $sauth->userId;
        $this->sauth->reject_non_dev('Error Code: 33134748e4');
    }

    public function removeAllBlocksFromUserGroup($groupId)
    {
        if (confirm_box(true)) {
            $users = $this->helper->removeAllBlocksFromUserGroup($groupId);
            print_r(array_values($users));
            $html[] = '<div><h2>Removed all user blocks from:</h2>';
            $html = array_merge($html, $users);
            $html[] = '</div>';
            $html = implode('', $html);
            trigger_error($html);
        } else {
            $groupData = $this->helper->getUserGroupData($groupId);
            $groupName = $groupData['group_name'];
            confirm_box(false, "You are about to remove all blocks issued by ${groupName}");
        }
        return new Response('Reload');
    }

    public function viewUserBlock()
    {
        $username = $this->request->variable('username', '');
        $resp = $this->helper->getUserBlocks($username);
        $resp = new JsonResponse($resp);
        $resp->setEncodingOptions(JSON_NUMERIC_CHECK);
        return $resp;
    }

    public function viewUserBlockLog()
    {
        $username = $this->request->variable('username', '');
        $resp = $this->helper->getUserBlocksLog($username);
        $resp = new JsonResponse($resp);
        $resp->setEncodingOptions(JSON_NUMERIC_CHECK);
        return $resp;
    }
}
