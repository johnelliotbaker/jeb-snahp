<?php

namespace jeb\snahp\core\Rest\Views;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

require_once 'ext/jeb/snahp/core/Rest/Serializers.php';
require_once 'ext/jeb/snahp/core/Rest/Utils.php';
require_once 'ext/jeb/snahp/core/Rest/RedBeanSetup.php';

use jeb\snahp\core\Rest\RedBeanSetup;

class GenericAPIView
{
    use RedBeanSetup;

    protected $queryset;
    protected $serializerClass;
    protected $paginationClass;


    public function getSerializer($instance=null, $data=null, $kwargs=[])/*{{{*/
    {
        return new $this->serializerClass($instance, $data, $kwargs);
    }/*}}}*/

    public function getObject()/*{{{*/
    {
        $pk = (int) $this->params['pk'];
        return \R::findOneForUpdate($this->model::TABLE_NAME, 'id=?', [$pk]);
    }/*}}}*/

    public function getQueryset()/*{{{*/
    {
        $queryset = $this->queryset;
        return $queryset;
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
        return new JsonResponse($instance);
        // $serializer = $this->getSerializer($instance);
        // return new JsonResponse($serializer->initialData);
    }/*}}}*/
}

trait ListModelMixin
{
    public function list($request)/*{{{*/
    {
        $queryset = $this->filterQueryset($this->getQueryset());
        $page = $this->paginateQueryset($queryset);
        if ($page) {
            $serializer = $this->getSerializer($page->objectList);
            return $this->getPaginatedResponse($serializer->data());
            // return new JsonResponse($serializer->data());
        }
        $serializer = $this->getSerializer($queryset);
        return new JsonResponse($serializer->data());
    }/*}}}*/
}

trait CreateModelMixin
{
    public function create($request)/*{{{*/
    {
        $serializer = $this->getSerializer(null, getRequestData($request));
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
        return $instance;
    }/*}}}*/

    public function performCreateWithForeignKey($serializer)/*{{{*/
    {
        $instance = $serializer->save();
        $foreignInstance = $this->getForeignObject();
        $selfName = $this->model::TABLE_NAME;
        $ownlistname = 'own' . ucfirst($selfName) . 'List';
        $foreignInstance->$ownlistname[] = $instance;
        \R::store($foreignInstance);
        return $instance;
    }/*}}}*/

    public function getForeignObject()/*{{{*/
    {
        $foreignName = $this->model::FOREIGN_NAME;
        $foreignPk = $this->getForeignPk(0);
        $foreignInstance = \R::findOneForUpdate($foreignName, 'id=?', [$foreignPk]);
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
        \R::trash($instance);
    }/*}}}*/
}
