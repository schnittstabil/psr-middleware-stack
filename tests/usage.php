#!/usr/bin/env php
<?php

namespace Schnittstabil;

require __DIR__.'/../vendor/autoload.php';

use Schnittstabil\Psr\Middleware\Stack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\Middleware\RequestHandlerInterface;

// create the core of the onion stack, i.e. the innermost request handler
$core = function (ServerRequestInterface $request):ResponseInterface {
    return new \Zend\Diactoros\Response();
};

// create some middlewares to wrap around the core
$middlewares = [];

$middlewares[] = function (ServerRequestInterface $request, RequestHandlerInterface $next):ResponseInterface {
    // delegate $request to the next request handler, i.e. $core
    $response = $next($request);

    return $response->withHeader('content-type', 'application/json; charset=utf-8');
};

$middlewares[] = function (ServerRequestInterface $request, RequestHandlerInterface $next):ResponseInterface {
    // delegate $request to the next request handler, i.e. the middleware right above
    $response = $next($request);

    return $response->withHeader('X-PoweredBy', 'Unicorns');
};

// create an onion style middleware stack
$stack = new Stack($core, ...$middlewares);

// and process an incoming server request
$response = $stack(new \Zend\Diactoros\ServerRequest());

exit($response->getHeader('X-PoweredBy')[0] === 'Unicorns' ? 0 : 1);
