# vaibhavpandeyvpz/vidyut
No frills [PSR-7](http://www.php-fig.org/psr/psr-7/) middleware based on [PSR-15](https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware.md) draft.

> Vidyut: `विद्युत्` (Electricity)

[![Build status][build-status-image]][build-status-url]
[![Code Coverage][code-coverage-image]][code-coverage-url]
[![Latest Version][latest-version-image]][latest-version-url]
[![Downloads][downloads-image]][downloads-url]
[![PHP Version][php-version-image]][php-version-url]
[![License][license-image]][license-url]

[![SensioLabsInsight][insights-image]][insights-url]

Install
-------
```bash
composer require vaibhavpandeyvpz/vidyut

# You will also need a PSR-7 implementation
composer require vaibhavpandeyvpz/sandesh
```

Usage
-----
```php
<?php

/**
 * @desc Your middleware can eitherbe an instance of Interop\Http\ServerMiddleware\MiddlewareInterface
 *      or a simple callable with similar signature.
 */
$pipeline = new Vidyut\Pipeline();

$pipeline->pipe(function ($request, $delegate) {
    if ($request->getUri()->getPath() === '/login') {
        $response = new Sandesh\Response();
        $response->getBody()->write('Login');
        return $response;
    }
    return $delegate->process($request);
});

$pipeline->pipe(function ($request, $delegate) {
    if ($request->getUri()->getPath() === '/logout') {
        $response = new Sandesh\Response();
        $response->getBody()->write('Logout');
        return $response;
    }
    return $delegate->process($request);
});

$pipeline->pipe(function () {
    $response = new Sandesh\Response();
    $response->getBody()->write('Not Found');
    return $response->withStatus(404);
});

$response = $pipeline->process(new Sandesh\ServerRequest());
```

License
------
See [LICENSE.md][license-url] file.

[build-status-image]: https://img.shields.io/travis/vaibhavpandeyvpz/vidyut.svg?style=flat-square
[build-status-url]: https://travis-ci.org/vaibhavpandeyvpz/vidyut
[code-coverage-image]: https://img.shields.io/codecov/c/github/vaibhavpandeyvpz/vidyut.svg?style=flat-square
[code-coverage-url]: https://codecov.io/gh/vaibhavpandeyvpz/vidyut
[latest-version-image]: https://img.shields.io/github/release/vaibhavpandeyvpz/vidyut.svg?style=flat-square
[latest-version-url]: https://github.com/vaibhavpandeyvpz/vidyut/releases
[downloads-image]: https://img.shields.io/packagist/dt/vaibhavpandeyvpz/vidyut.svg?style=flat-square
[downloads-url]: https://packagist.org/packages/vaibhavpandeyvpz/vidyut
[php-version-image]: http://img.shields.io/badge/php-5.3+-8892be.svg?style=flat-square
[php-version-url]: https://packagist.org/packages/vaibhavpandeyvpz/vidyut
[license-image]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[license-url]: LICENSE.md
[insights-image]: https://insight.sensiolabs.com/projects/24a30378-57cb-49c0-b75d-900172e98457/small.png
[insights-url]: https://insight.sensiolabs.com/projects/24a30378-57cb-49c0-b75d-900172e98457
