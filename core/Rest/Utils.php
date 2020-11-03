<?php

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

function getOwnList($bean, $tableName)
{
    return $bean->{'own' . ucfirst($tableName) . 'List'};
}
