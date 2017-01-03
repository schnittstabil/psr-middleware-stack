<?php

namespace Schnittstabil\Psr\Middleware\Helpers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

class FinalHandler implements DelegateInterface
{
    protected $body;

    public function __construct(string $body)
    {
        $this->body = $body;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(ServerRequestInterface $request):ResponseInterface
    {
        return new Response($this->body);
    }
}
