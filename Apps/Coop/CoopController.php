<?php
namespace jeb\snahp\Apps\Coop;

use Symfony\Component\HttpFoundation\JsonResponse;

class CoopController
{
    public function __construct($sauth, $helper)
    {
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        $this->sauth->reject_anon("Error Code: a5e8ee80c7");
    }

    public function postsInTopic($topicId)
    {
        $perm = $this->helper->canViewPostsInTopic($this->userId, $topicId);
        if (!$perm) {
            throwHttpException(403, "Error Code: aef9f973d4");
        }
        $maxPostCount = 40;
        $data = $this->helper->getPostsInTopic($topicId, $maxPostCount);
        $response = (new JsonResponse($data))->setEncodingOptions(
            JSON_UNESCAPED_SLASHES
        );
        return $response;
    }
}
