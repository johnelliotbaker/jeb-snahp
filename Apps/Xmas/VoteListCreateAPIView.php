<?php

namespace jeb\snahp\Apps\Xmas;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;
use jeb\snahp\core\Rest\Permissions\AllowAnyPermission;

class VoteListCreateAPIView extends ListCreateAPIView
{
    // protected $foreignNameParam = 'urlParam';
    protected $serializerClass = 'jeb\snahp\core\Rest\Serializers\ModelSerializer';
    protected $request;
    protected $sauth;
    protected $model;

    public function __construct($request, $sauth, $model)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->model = $model;
        $this->permissionClasses = [
            // new AllowDevPermission($sauth),
            new AllowAnyPermission($sauth),
        ];
    }

    public function performPreCreate($serializer)/*{{{*/
    {
        $schedule = getXmasConfig('schedule', 0);
        $index = getTimeIndex(
            time(),
            $schedule['start'],
            $schedule['duration'],
            $schedule['division']
        );
        if ($index === $schedule['division']) {
            header_remove();
            http_response_code(500);
            header('Content-Type: application/json');
            print_r(json_encode(['status' => 'ERROR', 'message'=>'Voting period is over.']));
            die();
            throw new \Exception('Too late. Error Code: 68eec05f1d');
        }
        $serializer->_validatedData['user'] = $this->sauth->userId;
        $serializer->_validatedData['period'] = $index;
        return $serializer;
    }/*}}}*/

    public function create($request)/*{{{*/
    {
        try {
            return parent::create($request);
        } catch (\jeb\snahp\core\Rest\RedExceptionSQL $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate entry')) {
                return new JsonResponse(['status'=>'ERROR', 'message'=>'You cannot vote more than once each period.'], 403);
            }
        }
        return new JsonResponse(['status'=>'ERROR', 'message'=>'Unknown Error.'], 400);
    }/*}}}*/

}
