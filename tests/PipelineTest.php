<?php

/*
 * This file is part of vaibhavpandeyvpz/vidyut package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Vidyut;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sandesh\ResponseFactory;
use Sandesh\ServerRequest;
use Sandesh\Uri;

/**
 * Class PipelineTest
 * @package Vidyut
 */
class PipelineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param ServerRequestInterface $request
     * @dataProvider provideProcessRequests
     */
    public function testProcess(ServerRequestInterface $request)
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate) {
            if ($request->getUri()->getPath() === '/login') {
                $response =  (new ResponseFactory())->createResponse();
                $response->getBody()->write('Login');
                return $response;
            }
            return $delegate->handle($request);
        });
        $pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate) {
            if ($request->getUri()->getPath() === '/logout') {
                $response =  (new ResponseFactory())->createResponse();
                $response->getBody()->write('Logout');
                return $response;
            }
            return $delegate->handle($request);
        });
        $pipeline->pipe(function () {
            $response =  (new ResponseFactory())->createResponse();
            $response->getBody()->write('This URL does not exist.');
            return $response->withStatus(404);
        });
        $response = $pipeline->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        if ($request->getUri()->getPath() === '/login') {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('Login', (string)$response->getBody());
        } elseif ($request->getUri()->getPath() === '/logout') {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('Logout', (string)$response->getBody());
        } else {
            $this->assertEquals(404, $response->getStatusCode());
            $this->assertEquals('This URL does not exist.', (string)$response->getBody());
        }
    }

    public function testEmptyMiddleware()
    {
        $pipeline = new Pipeline();
        $this->setExpectedException(\RuntimeException::class);
        $pipeline->handle(new ServerRequest());
    }

    public function testInvalidMiddleware()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe('some_invalid_func');
        $this->setExpectedException(\InvalidArgumentException::class);
        $pipeline->handle(new ServerRequest());
    }

    public function testMiddlewareViaConstructor()
    {
        $pipeline = new Pipeline([
            function (ServerRequestInterface $request, RequestHandlerInterface $delegate) {
                $response = $delegate->handle($request);
                $response->getBody()->write('!');
                return $response;
            },
            function () {
                $response =  (new ResponseFactory())->createResponse();
                $response->getBody()->write('Hello');
                return $response;
            }
        ]);
        $response = $pipeline->handle(new ServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello!', (string)$response->getBody());
    }

    public function provideProcessRequests()
    {
        $uri = new Uri();
        return [
            [new ServerRequest('GET', $uri->withPath('/login'))],
            [new ServerRequest('GET', $uri->withPath('/logout'))],
            [new ServerRequest('GET', $uri->withPath('/dashboard'))],
            [new ServerRequest('GET', $uri->withPath('/'))]
        ];
    }
}
