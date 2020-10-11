<?php

namespace jeb\snahp\core\Rest\Views;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Serializers.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Utils.php';

use \R as R;

trait View
{
    protected $permissionClasses = [];

    public function getPermissions()
    {
        $perms = [];
        foreach ($this->permissionClasses as $k => $v) {
            $perms[] = $v;
        }
        return $perms;
    }

    public function checkPermissions($request, $userId, $kwargs=[])
    {
        if (!$perms = $this->getPermissions()) {
            return;
        }
        foreach ($perms as $perm) {
            if ($perm->hasPermission($request, $userId, $kwargs)) {
                return;
            }
        }
        throw new \Exception('You do not have the permission to view this resource. Error Code: afb80c4efc');
    }

    public function checkObjectPermissions($request, $userId, $object, $kwargs=[])
    {
        if (!$perms = $this->getPermissions()) {
            return;
        }
        foreach ($perms as $perm) {
            if ($perm->hasObjectPermission($request, $userId, $object, $kwargs)) {
                return;
            }
        }
        throw new \Exception('You do not have the permission to view this resource. Error Code: 17f3c0bc47');
    }
}

trait ModelSerializerMixin
{
    public function getSerializer($instance=null, $data=null, $kwargs=[])/*{{{*/
    {
        if ($this->model) {
            $kwargs['model'] = $this->model;
        }
        return new $this->serializerClass($instance, $data, $kwargs);
    }/*}}}*/
}

trait SortByPriorityMixin
{
    public function getQueryset()/*{{{*/
    {
        $data = getRequestData($this->request);
        if (!$data) {
            return array_values(R::find($this->model::TABLE_NAME, 'ORDER BY priority DESC'));
        }
        $sqlAry = [];
        foreach ($data as $varname => $value) {
            $sqlAry[] = "${varname}='${value}'";
        }
        $sqlAry[] = 'ORDER BY priority DESC';
        $where = implode(' AND ', $sqlAry);
        return array_values(R::find($this->model::TABLE_NAME, $where));
    }/*}}}*/
}

class GenericAPIView
{
    use View;
    use ModelSerializerMixin;

    protected $queryset;
    protected $serializerClass;
    protected $paginationClass;


    // public function getSerializer($instance=null, $data=null, $kwargs=[])/*{{{*/
    // {
    //     return new $this->serializerClass($instance, $data, $kwargs);
    // }/*}}}*/

    public function getObject()/*{{{*/
    {
        $pk = (int) $this->params['pk'];
        $object = R::findOne($this->model::TABLE_NAME, 'id=?', [$pk]);
        if (!$object) {
            throw new \Exception('Request resource was not found. Error Code: 3807589034');
        }
        $this->checkObjectPermissions($this->request, $this->sauth->userId, $object);
        return $object;
    }/*}}}*/

    public function getQueryset()/*{{{*/
    {
        if ($data = getRequestData($this->request)) {
            foreach ($data as $varname => $value) {
                $sqlAry[] = "${varname}='${value}'";
            }
        }
        // Must be the last one
        $sqlAry = $this->orderBySqlStatement ? [$this->orderBySqlStatement] : [];
        $where = implode(' AND ', $sqlAry);
        return array_values(R::find($this->model::TABLE_NAME, $where));

        // $data = getRequestData($this->request);
        // if (!$data) {
        //     return array_values(R::find($this->model::TABLE_NAME));
        // }
        // foreach ($data as $varname => $value) {
        //     $sqlAry[] = "${varname}='${value}'";
        // }
        // $where = implode(' AND ', $sqlAry);
        // return array_values(R::find($this->model::TABLE_NAME, $where));
    }/*}}}*/

    public function filterQueryset($queryset)/*{{{*/
    {
        return $queryset;
    }/*}}}*/

    public function paginateQueryset($queryset)/*{{{*/
    {
        if ($this->paginator === null) {
            return;
        }
        return $this->paginator->paginateQueryset($queryset, $this->request);
    }/*}}}*/

    public function getPaginatedResponse($data)/*{{{*/
    {
        return $this->paginator->getPaginatedResponse($data);
    }/*}}}*/

    public function getForeignPk($default='')/*{{{*/
    {
        return $this->request->variable($this->foreignNameParam, $default);
    }/*}}}*/
}


class RetrieveUpdateDestroyAPIView extends GenericAPIView
{
    use RetrieveModelMixin;
    use UpdateModelMixin;
    use DestroyModelMixin;

    public function dispatch($id)/*{{{*/
    {
        $methodName = getRequestMethod($this->request);
        $this->params['pk'] = $id;
        switch ($methodName) {
        case 'OPTIONS':
            return new JsonResponse([], 200);
        case 'GET':
            return $this->get($this->request);
        case 'DELETE':
            return $this->delete($this->request);
        // case 'PUT':
            // return $this->put($this->request);
        case 'PATCH':
            return $this->patch($this->request);
        default:
            return new JsonResponse(["method"=>$methodName], 404);
        }
    }/*}}}*/

    public function get($request)/*{{{*/
    {
        return $this->retrieve($request);
    }/*}}}*/

    public function put($request)/*{{{*/
    {
        return $this->update($request);
    }/*}}}*/

    public function patch($request)/*{{{*/
    {
        return $this->partialUpdate($request);
    }/*}}}*/

    public function delete($request)/*{{{*/
    {
        return $this->destroy($request);
    }/*}}}*/
}

class ListCreateAPIView extends GenericAPIView
{
    use ListModelMixin;
    use CreateModelMixin;

    public function dispatch()/*{{{*/
    {
        $methodName = getRequestMethod($this->request);
        switch ($methodName) {
        case 'OPTIONS':
            return new JsonResponse([], 200);
        case 'GET':
            return $this->get($this->request);
        case 'POST':
            return $this->post($this->request);
        default:
            return new JsonResponse(["method"=>$methodName], 404);
        }
    }/*}}}*/

    public function get($request)/*{{{*/
    {
        return $this->list($request);
    }/*}}}*/

    public function post($request)/*{{{*/
    {
        return $this->create($request);
    }/*}}}*/
}

trait UpdateModelMixin
{
    public function update($request, $partial=false)/*{{{*/
    {
        [$instance, $data] = [$this->getObject(), getRequestData($request)];
        if (!$instance) {
            trigger_error("Instance not found. Error Code: d9718fb2e8");
        }
        $serializer = $this->getSerializer($instance, $data);
        if ($serializer->isValid()) {
            $data = $this->performUpdate($serializer);
            return new JsonResponse($data, 200);
        }
        return new JsonResponse([], 200);
    }/*}}}*/

    public function performUpdate($serializer)/*{{{*/
    {
        return $serializer->save();
    }/*}}}*/

    public function partialUpdate($request)/*{{{*/
    {
        $partial = true;
        return $this->update($request, $partial);
    }/*}}}*/
}

trait RetrieveModelMixin
{
    public function retrieve($request)/*{{{*/
    {
        $instance = $this->getObject();
        if (!$instance) {
            return new JsonResponse([], 404);
        }
        $serializer = $this->getSerializer($instance, $instance->export());
        $serializer->fillInitialDataWithDefaultValues();
        $serializer->isValid();
        $serializedData = $serializer->serialize();
        $serializedData['id'] = $instance->id;
        return new JsonResponse($serializedData);
    }/*}}}*/
}

trait ListModelMixin
{
    public function list($request)/*{{{*/
    {
        $this->checkPermissions($request, $this->sauth->userId);
        $queryset = $this->filterQueryset($this->getQueryset());
        $page = $this->paginateQueryset($queryset);
        if ($page) {
            $serializer = $this->getSerializer($page->objectList);
            return $this->getPaginatedResponse($serializer->data());
        }
        $serializer = $this->getSerializer($queryset);
        return new JsonResponse($serializer->data());
    }/*}}}*/
}

trait CreateModelMixin
{
    public function create($request)/*{{{*/
    {
        $this->checkPermissions($request, $this->sauth->userId);
        $serializer = $this->getSerializer(null, getRequestData($request));
        $serializer->fillInitialDataWithDefaultValues();
        if ($serializer->isValid()) {
            $instance = $this->performCreate($serializer);
            return new JsonResponse($instance, 201);
        }
        return new Response('Could not create.', 400);
    }/*}}}*/

    public function performCreate($serializer)/*{{{*/
    {
        $model = $this->model;
        if (defined(get_class($model).'::FOREIGN_NAME')) {
            $instance = $this->performCreateWithForeignKey($serializer);
        } else {
            $instance = $serializer->save();
        }
        $instance = $this->performPostCreate($instance);
        return $instance;
    }/*}}}*/

    public function performPostCreate($object)/*{{{*/
    {
        return $object;
    }/*}}}*/

    public function performCreateWithForeignKey($serializer)/*{{{*/
    {
        $instance = $serializer->save();
        $foreignInstance = $this->getForeignObject();
        $selfName = $this->model::TABLE_NAME;
        $ownlistname = 'own' . ucfirst($selfName) . 'List';
        $foreignInstance->$ownlistname[] = $instance;
        R::store($foreignInstance);
        return $instance;
    }/*}}}*/

    public function getForeignObject()/*{{{*/
    {
        $foreignName = $this->model::FOREIGN_NAME;
        $foreignPk = $this->getForeignPk(0);
        $foreignInstance = R::findOne($foreignName, 'id=?', [$foreignPk]);
        if (!$foreignInstance) {
            http_response_code(404);
            $selfName = $this->model::TABLE_NAME;
            $resp = ucfirst("${selfName} must have a valid parent ${foreignName}. Error Code: e65ca2603a");
            print($resp);
            die();
        }
        return $foreignInstance;
    }/*}}}*/
}

trait DestroyModelMixin
{
    public function destroy($request)/*{{{*/
    {
        $instance = $this->getObject($request);
        if ($instance) {
            $this->performDestroy($instance);
        }
        return new JsonResponse([], 200);
    }/*}}}*/

    public function performDestroy($instance)/*{{{*/
    {
        R::trash($instance);
    }/*}}}*/
}
