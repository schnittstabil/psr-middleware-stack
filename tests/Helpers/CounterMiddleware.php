<?php

namespace Schnittstabil\Psr\Middleware\Helpers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;

class CounterMiddleware implements MiddlewareInterface
{
    protected $index;

    public function __construct(int $index = 0)
    {
        $this->index = $index;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate):ResponseInterface
    {
        $response = $delegate->process($request);
        $response->getBody()->write((string) $this->index++);

        return $response;
    }
}
