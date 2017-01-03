<?php

namespace Schnittstabil\Psr\Middleware;

use Exception;
use Schnittstabil\Psr\Middleware\Helpers\CounterMiddleware;
use Schnittstabil\Psr\Middleware\Helpers\FinalHandler;
use Schnittstabil\Psr\Middleware\Helpers\Response;
use Schnittstabil\Psr\Middleware\Helpers\MultiDelegationMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;

class StackTest extends \PHPUnit_Framework_TestCase
{
    use \VladaHejda\AssertException;

    public function testEmptyStacksShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');

        $sut = new Stack($finalHandler);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!', (string) $response->getBody());
    }

    public function testSingleMiddelwareShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware = new CounterMiddleware(0);

        $sut = new Stack($finalHandler, $middleware);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!0', (string) $response->getBody());
    }

    public function testDoubleMultipleMiddlewareShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware0 = new CounterMiddleware(0);
        $middleware9 = new CounterMiddleware(9);

        $sut = new Stack($finalHandler, $middleware0, $middleware9);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!09', (string) $response->getBody());
    }

    public function testMiddlewareReuseShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware = new CounterMiddleware(0);

        $sut = new Stack($finalHandler, $middleware, $middleware);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!01', (string) $response->getBody());
    }

    public function testMultipleMiddlewaresShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            new CounterMiddleware(0),
            new CounterMiddleware(1),
            new CounterMiddleware(2),
            new CounterMiddleware(3),
        ];

        $sut = new Stack($finalHandler, ...$middlewares);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!0123', (string) $response->getBody());
    }

    public function testMultiDelegationMiddlewaresShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            new CounterMiddleware(1),
            new MultiDelegationMiddleware(42),
        ];

        $sut = new Stack($finalHandler, ...$middlewares);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!42', (string) $response->getBody());
    }

    public function testCallbackMiddelwareShouldBeValid()
    {
        $finalHandler = new FinalHandler('Final!');
        $middleware = new class() implements MiddlewareInterface {
            function process(ServerRequestInterface $request, DelegateInterface $delegate)
            {
                static $index = 0;

                $response = $delegate->process($request);
                $response->getBody()->write((string) $index++);

                return $response;
            }
        };

        $sut = new Stack($finalHandler, $middleware);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Final!0', (string) $response->getBody());
    }

    public function testMiddlewaresCanHandleCoreExceptions()
    {
        $finalHandler = new class() implements DelegateInterface {
            function process(ServerRequestInterface $request):ResponseInterface
            {
                throw new Exception('Oops, something went wrong!', 500);
            }
        };
        $middlewares = [
            new class() implements MiddlewareInterface {
                function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    try {
                        $response = $delegate->process($request);
                    } catch (Exception $e) {
                        return new Response('Catched: '.$e->getMessage(), $e->getCode());
                    }
                }
            },
        ];

        $sut = new Stack($finalHandler, ...$middlewares);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Catched: Oops, something went wrong!', (string) $response->getBody());
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testMiddlewaresCanHandleMiddlewareExceptions()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            new class() implements MiddlewareInterface {
                function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    $response = $delegate->process($request);
                    throw new Exception('Oops, something went wrong!', 500);
                }
            },
            new class() implements MiddlewareInterface {
                function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    try {
                        $response = $delegate->process($request);
                    } catch (Exception $e) {
                        return new Response('Catched: '.$e->getMessage(), $e->getCode());
                    }
                }
            },
        ];

        $sut = new Stack($finalHandler, ...$middlewares);
        $response = $sut->process(new ServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Catched: Oops, something went wrong!', (string) $response->getBody());
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testMiddlewareCallerHaveToHandleCoreExceptions()
    {
        $finalHandler = new class() implements DelegateInterface {
            function process(ServerRequestInterface $request):ResponseInterface
            {
                throw new Exception('Oops, something went wrong!', 500);
            }
        };
        $middlewares = [
            new class() implements MiddlewareInterface {
                function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    $response = $delegate->process($request);

                    return $response;
                }
            },
        ];

        $sut = new Stack($finalHandler, ...$middlewares);

        $this->assertException(function () use ($sut) {
            $sut->process(new ServerRequest());
        }, Exception::class, 500, 'Oops, something went wrong!');
    }

    public function testMiddlewareCallerHaveToHandleMiddlewareExceptions()
    {
        $finalHandler = new FinalHandler('Final!');
        $middlewares = [
            new class() implements MiddlewareInterface {
                function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    $response = $delegate->process($request);
                    throw new Exception('Oops, something went wrong!', 500);
                }
            },
            new class() implements MiddlewareInterface {
                function process(ServerRequestInterface $request, DelegateInterface $delegate)
                {
                    return $delegate->process($request);
                }
            },
        ];

        $sut = new Stack($finalHandler, ...$middlewares);

        $this->assertException(function () use ($sut) {
            $sut->process(new ServerRequest());
        }, Exception::class, 500, 'Oops, something went wrong!');
    }
}
