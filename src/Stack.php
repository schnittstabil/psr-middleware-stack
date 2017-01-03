<?php

namespace Schnittstabil\Psr\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;

class Stack implements DelegateInterface
{
    /**
     * @var int
     */
    protected $index;

    /**
     * @var DelegateInterface
     */
    protected $core;

    /**
     * @var MiddlewareInterface[]
     */
    protected $middlewares;

    /**
     * Constructs an onion style PSR-15 middleware stack.
     *
     * @param DelegateInterface     $core        the innermost delegate
     * @param MiddlewareInterface[] $middlewares the middlewares to wrap around the core
     */
    public function __construct(DelegateInterface $core, MiddlewareInterface ...$middlewares)
    {
        $this->core = $core;
        $this->middlewares = $middlewares;
        $this->index = count($middlewares);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request):ResponseInterface
    {
        if ($this->index === 0) {
            return $this->core->process($request);
        }

        --$this->index;

        try {
            $topMiddleware = $this->middlewares[$this->index];

            return $topMiddleware->process($request, $this);
        } finally {
            ++$this->index;
        }
    }
}
