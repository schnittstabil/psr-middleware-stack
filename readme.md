# Schnittstabil\Psr\Middleware\Stack [![Build Status](https://travis-ci.org/schnittstabil/psr-middleware-stack.svg?branch=master)](https://travis-ci.org/schnittstabil/psr-middleware-stack) [![Coverage Status](https://coveralls.io/repos/schnittstabil/psr-middleware-stack/badge.svg?branch=master&service=github)](https://coveralls.io/github/schnittstabil/psr-middleware-stack?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/schnittstabil/psr-middleware-stack/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/schnittstabil/psr-middleware-stack/?branch=master)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/c2924db1-4e19-49ec-8542-179fdceba998/big.png)](https://insight.sensiolabs.com/projects/c2924db1-4e19-49ec-8542-179fdceba998)

> :bomb: **EXPERIMENTAL – `callables+interface` version of PSR-15** :bomb:
>
> Onion style [PSR-15](https://github.com/middlewares/awesome-psr15-middlewares) middleware stack


## Install

```
composer require schnittstabil/psr-middleware-stack
```


## Usage

```php
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
```


## API

### `Schnittstabil\Psr\Middleware\Stack implements RequestHandlerInterface`

#### `Stack::__construct`

```php
/**
 * Constructs an onion style PSR-15 middleware stack.
 *
 * @param callable|RequestHandlerInterface       $core        the innermost request handler
 * @param (callable|ServerMiddlewareInterface)[] $middlewares the middlewares to wrap around the core
 */
public function __construct(callable $core, callable ...$middlewares)
```

#### Inherited from `RequestHandlerInterface::__invoke`

```php
/**
 * Process an incoming server request and return the response.
 *
 * @param ServerRequestInterface $request
 *
 * @return ResponseInterface
 */
public function __invoke(ServerRequestInterface $request);
```


## License

MIT © [Michael Mayer](http://schnittstabil.de)
