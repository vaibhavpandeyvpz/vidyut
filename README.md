# Vidyut

[![Latest Version](https://img.shields.io/packagist/v/vaibhavpandeyvpz/vidyut.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/vidyut)
[![Downloads](https://img.shields.io/packagist/dt/vaibhavpandeyvpz/vidyut.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/vidyut)
[![PHP Version](https://img.shields.io/packagist/php-v/vaibhavpandeyvpz/vidyut.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/vidyut)
[![License](https://img.shields.io/packagist/l/vaibhavpandeyvpz/vidyut.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/vaibhavpandeyvpz/vidyut/tests.yml?branch=master&style=flat-square)](https://github.com/vaibhavpandeyvpz/vidyut/actions)

No frills [PSR-7](http://www.php-fig.org/psr/psr-7/) request handler based on [PSR-15](https://www.php-fig.org/psr/psr-15/) specification.

> **Vidyut** (`विद्युत्`) - Sanskrit for "Electricity", representing the flow of requests through the middleware pipeline.

## Features

- ✅ **PSR-15 Compliant** - Full implementation of the PSR-15 middleware specification
- ✅ **PSR-7 Compatible** - Works with any PSR-7 HTTP message implementation
- ✅ **Flexible Middleware** - Supports both `MiddlewareInterface` instances and callables
- ✅ **Fluent Interface** - Chain middleware using the `pipe()` method
- ✅ **Zero Dependencies** - Only requires PSR interfaces (PSR-7, PSR-15)
- ✅ **Modern PHP** - Built for PHP 8.2+ with modern language features
- ✅ **100% Test Coverage** - Fully tested with comprehensive test suite

## Installation

Install via Composer:

```bash
composer require vaibhavpandeyvpz/vidyut
```

**Note:** You'll also need a PSR-7 implementation. We recommend [sandesh](https://packagist.org/packages/vaibhavpandeyvpz/sandesh):

```bash
composer require vaibhavpandeyvpz/sandesh
```

## Quick Start

```php
<?php

use Vidyut\Pipeline;
use Sandesh\ServerRequestFactory;
use Sandesh\ResponseFactory;

// Create a new pipeline
$pipeline = new Pipeline();

// Add middleware using callables
$pipeline->pipe(function ($request, $delegate) {
    if ($request->getUri()->getPath() === '/login') {
        $response = (new ResponseFactory())->createResponse();
        $response->getBody()->write('Login Page');
        return $response;
    }
    return $delegate->handle($request);
});

// Add a final handler (404)
$pipeline->pipe(function () {
    $response = (new ResponseFactory())->createResponse();
    $response->getBody()->write('Page not found');
    return $response->withStatus(404);
});

// Handle a request
$request = (new ServerRequestFactory())
    ->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER);
$response = $pipeline->handle($request);
```

## Usage

### Basic Pipeline

The `Pipeline` class implements the PSR-15 `RequestHandlerInterface` and allows you to chain middleware:

```php
use Vidyut\Pipeline;

$pipeline = new Pipeline();

// Middleware can be callables
$pipeline->pipe(function ($request, $delegate) {
    // Process request
    $response = $delegate->handle($request);
    // Modify response
    return $response->withHeader('X-Custom', 'value');
});

// Or MiddlewareInterface instances
$pipeline->pipe(new MyMiddleware());

// Handle requests
$response = $pipeline->handle($request);
```

### Constructor Injection

You can also pass middleware directly to the constructor:

```php
$pipeline = new Pipeline([
    function ($request, $delegate) {
        // First middleware
        return $delegate->handle($request);
    },
    function ($request, $delegate) {
        // Second middleware
        return $delegate->handle($request);
    },
    function () {
        // Final handler
        return (new ResponseFactory())->createResponse(200);
    },
]);
```

### Middleware Types

#### Callable Middleware

Callables must accept two parameters:

- `ServerRequestInterface $request`
- `RequestHandlerInterface $delegate`

And return a `ResponseInterface`:

```php
$pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
    // Your middleware logic
    return $delegate->handle($request);
});
```

#### MiddlewareInterface Instances

You can use any class implementing `Psr\Http\Server\MiddlewareInterface`:

```php
class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            return (new ResponseFactory())->createResponse(401);
        }
        return $handler->handle($request);
    }
}

$pipeline->pipe(new AuthenticationMiddleware());
```

#### Array Callables

You can also use array callables:

```php
$middleware = new class {
    public function handle(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        return $delegate->handle($request);
    }
};

$pipeline->pipe([$middleware, 'handle']);
```

### Request Modification

Middleware can modify the request before passing it to the next handler:

```php
$pipeline->pipe(function ($request, $delegate) {
    // Add custom attribute
    $modifiedRequest = $request->withAttribute('user', $this->getUser($request));
    return $delegate->handle($modifiedRequest);
});
```

### Response Modification

Middleware can modify the response after receiving it from the next handler:

```php
$pipeline->pipe(function ($request, $delegate) {
    $response = $delegate->handle($request);
    // Add custom header
    return $response->withHeader('X-Processed-By', 'MyMiddleware');
});
```

### Early Termination

Middleware can stop the pipeline by returning a response without calling the delegate:

```php
$pipeline->pipe(function () {
    // Return immediately, next middleware won't be called
    return (new ResponseFactory())->createResponse(403, 'Forbidden');
});
```

### Fluent Interface

The `pipe()` method returns the pipeline instance, allowing method chaining:

```php
$pipeline = new Pipeline()
    ->pipe($middleware1)
    ->pipe($middleware2)
    ->pipe($middleware3);
```

## Requirements

- PHP 8.2 or higher
- A PSR-7 implementation (e.g., [sandesh](https://packagist.org/packages/vaibhavpandeyvpz/sandesh))

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/vaibhavpandeyvpz/vidyut).
