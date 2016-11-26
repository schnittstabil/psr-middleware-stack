<?php

namespace Schnittstabil\Psr\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Interop\Http\Middleware\DelegateInterface;

class Stack implements DelegateInterface
{
    /**
     * @var callable|DelegateInterface
     */
    protected $core;

    /**
     * @var callable[]|ServerMiddlewareInterface[]
     */
    protected $middlewares;

    /**
     * Constructs an onion style PSR-15 middleware stack.
     *
     * @param callable|DelegateInterface             $core        the innermost delegate
     * @param callable[]|ServerMiddlewareInterface[] $middlewares the middlewares to wrap around the core
     */
    public function __construct(callable $core, callable ...$middlewares)
    {
        $this->core = $core;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request):ResponseInterface
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
