<?php

namespace Schnittstabil\Psr\Middleware\Helpers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;

class MultiDelegationMiddleware implements MiddlewareInterface
{
    protected $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate):ResponseInterface
    {
        for ($i = 0; $i < $this->count; ++$i) {
            $response = $delegate->process($request);
        }

        return $response;
    }
}
