<?php

namespace Schnittstabil\Psr\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\Middleware\RequestHandlerInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;

class Stack implements RequestHandlerInterface
{
    /**
     * @var callable|RequestHandlerInterface
     */
    protected $core;

    /**
     * @var ServerMiddlewareInterface[]
     */
    protected $middlewares;

    /**
     * Constructs an onion style PSR-15 middleware stack.
     *
     * @param callable|RequestHandlerInterface       $core        the innermost request handler
     * @param (callable|ServerMiddlewareInterface)[] $middlewares the middlewares to wrap around the core
     */
    public function __construct(callable $core, callable ...$middlewares)
    {
        $this->core = $core;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request):ResponseInterface
    {
        if (count($this->middlewares) === 0) {
            $core = $this->core;

            return $core($request);
        }

        $copy = clone $this;
        $topMiddleware = array_pop($copy->middlewares);

        return $topMiddleware($request, $copy);
    }
}
