<?php

namespace jeb\snahp\Apps\DeadLinks;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php';

use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\Views\ListCreateAPIView;
use jeb\snahp\core\Rest\Permissions\Permission;
use jeb\snahp\core\Rest\Permissions\AllowDevPermission;

class ReporterPermission
{
    public function __construct($sauth, $Entry)
    {
        $this->sauth = $sauth;
        $this->Entry = $Entry;
    }

    public function hasPermission($request, $userId, $kwargs=[])/*{{{*/
    {
        [$topicId, $topicData] = [$kwargs['topicId'], $kwargs['topicData']];
        $method = getRequestMethod($request);
        switch ($method) {
        case 'GET':
            if ((int) $topicData['topic_poster'] === (int) $this->sauth->userId || $this->sauth->is_dev()) {
                return true;
            }
            break;
        case 'POST':
            $data = getRequestData($request);
            [$type, $status] = [$data['type'], $data['status']];
            $this->checkCreatePermission($topicId, $type);
            return true;
        default:
            return false;
        }
        return false;
    }/*}}}*/

    public function checkCreatePermission($topicId, $type)/*{{{*/
    {
        $_REGISTRY = [
            'Request' => 'canCreateRequest',
            'Report' => 'canCreateReport',
            'Action' => 'canCreateAction',
        ];
        $this->{$_REGISTRY[$type]}($topicId);
    }/*}}}*/

    public function canCreateReport($topicId)/*{{{*//*}}}*//*{{{*/
    {
        $entry = $this->Entry->activeEntry($topicId);
        if ($entry) {
            throw new PendingReportError();
        }
    }/*}}}*/

    public function canCreateRequest($topicId)/*{{{*/
    {
        if (!$this->Entry->isAuthor($topicId, $this->sauth->userId)) {
            throw new \Exception('You do not have the permission to access this page. Error Code: 90d17e3c3f');
        }
        $entry = $this->Entry->activeEntry($topicId);
        if ($entry->type === 'Request') {
            throw new PendingRequestError();
        }
        if ($entry->type !== 'Report') {
            throw new ReportDoesNotExist();
        }
    }/*}}}*/

    public function canCreateAction($topicId)/*{{{*/
    {
        if (!$this->sauth->is_dev()) {
            throw new \Exception('You do not have permission to view this page. Error Code: c203866c4f');
        }
        $entry = $this->Entry->activeEntry($topicId);
        if ($entry->type !== 'Request') {
            throw new RequestDoesNotExist();
        }
    }/*}}}*/

    public function hasObjectPermission($request, $userId, $object, $kwargs=[])/*{{{*/
    {
        return false;
    }/*}}}*/
}

class EntryListCreateAPIView extends ListCreateAPIView
{
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
            new ReporterPermission($sauth, $model),
            // new AllowDevPermission($sauth),
        ];
        $this->sauth->reject_new_users('Error Code: d5ab149440');
    }

    public function checkPermissions($request, $userId, $kwargs=[])/*{{{*/
    {
        // To fill kwargs with needed info for POST
        if (!array_key_exists('topicId', $kwargs)) {
            $data = getRequestData($request);
            $topicId = (int) $data['topic'];
            if (!isset($data['topic']) || !isset($data['type'])) {
                throw new \Exception('Required data not found. Error Code: 17bf6d9b81');
            }
            $kwargs = [
                'topicId' => $data['topic'],
                'topicData' => $this->model->getTopicData($topicId),
            ];
        }
        return parent::checkPermissions($request, $userId, $kwargs);
    }/*}}}*/

    public function viewByTopicId($topicId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $topicData = $this->model->getTopicData($topicId);
        if (!$topicData) {
            throw new \Exception('That topic does not exist. Error Code: 334c9082e4');
        }
        $this->checkPermissions($this->request, $this->sauth, ['topicId' => $topicId, 'topicData' => $topicData]);
        $qs = $this->model->getQueryset('topic=? ORDER BY id DESC', [$topicId]);
        return new JsonResponse(array_values($qs));
    }/*}}}*/
}

class RequestDoesNotExist extends \Exception /*{{{*/
{
    public function __construct($strn='Request does not exist. Error Code: f52224f58d')
    {
        parent::__construct($strn);
    }
}/*}}}*/

class ReportDoesNotExist extends \Exception /*{{{*/
{
    public function __construct($strn='Report does not exist. Error Code: 1169cbc3e1')
    {
        parent::__construct($strn);
    }
}/*}}}*/

class PendingActionError extends \Exception /*{{{*/
{
    public function __construct($strn='This topic has a pending action. Error Code: 71426ecd63')
    {
        parent::__construct($strn);
    }
}/*}}}*/

class PendingRequestError extends \Exception /*{{{*/
{
    public function __construct($strn='This topic has a pending request. Error Code: 05b7d48d54')
    {
        parent::__construct($strn);
    }
}/*}}}*/

class PendingReportError extends \Exception /*{{{*/
{
    public function __construct($strn='This topic has a pending report. Error Code: bf583cf637')
    {
        parent::__construct($strn);
    }
}/*}}}*/
