<?php

function getRequestMethod($request)
{
    return $request->server('REQUEST_METHOD', 'GET');
}

function getRequestData($request)
{
    $method = getRequestMethod($request);
    if (in_array($method, ['PUT', 'PATCH'])) {
        $params = file_get_contents('php://input');
        return json_decode($params, true);
    }
    foreach ($request->variable_names() as $varname) {
        $data[$varname] = $request->variable($varname, '', true);
    }
    return $data;
}

function buildAbsoluteUri($request, $data=[])
{
    $host = $request->server('HTTP_HOST');
    $requestUri = $request->server('REQUEST_URI');
    $query = $request->get_super_global();
    $query = array_merge($query, $data);
    $query = http_build_query($query);
    $pathInfo = strtok($requestUri, '?');

    $scheme = $request->server('HTTPS') === 'on' ? "https" : "http";
    $url = $scheme . "://{$request->server('HTTP_HOST')}{$pathInfo}?{$query}";
    return $url;
}

