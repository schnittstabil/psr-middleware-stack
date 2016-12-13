<?php

namespace Schnittstabil\Psr\Middleware\Helpers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\Middleware\RequestHandlerInterface;

class FinalHandler implements RequestHandlerInterface
{
    protected $body;

    public function __construct(string $body)
    {
        $this->body = $body;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(ServerRequestInterface $request):ResponseInterface
    {
        return new Response($this->body);
    }
}
