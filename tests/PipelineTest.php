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

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sandesh\Response;
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
        $pipeline->pipe(function (ServerRequestInterface $request, DelegateInterface $delegate) {
            if ($request->getUri()->getPath() === '/login') {
                $response = new Response();
                $response->getBody()->write('Login');
                return $response;
            }
            return $delegate->process($request);
        });
        $pipeline->pipe(function (ServerRequestInterface $request, DelegateInterface $delegate) {
            if ($request->getUri()->getPath() === '/logout') {
                $response = new Response();
                $response->getBody()->write('Logout');
                return $response;
            }
            return $delegate->process($request);
        });
        $pipeline->pipe(function () {
            $response = new Response();
            $response->getBody()->write('Not Found');
            return $response->withStatus(404);
        });
        $response = $pipeline->process($request);
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        if ($request->getUri()->getPath() === '/login') {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('Login', (string)$response->getBody());
        } elseif ($request->getUri()->getPath() === '/logout') {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('Logout', (string)$response->getBody());
        } else {
            $this->assertEquals(404, $response->getStatusCode());
            $this->assertEquals('Not Found', (string)$response->getBody());
        }
    }

    public function testEmptyMiddleware()
    {
        $pipeline = new Pipeline();
        $this->setExpectedException('RuntimeException');
        $pipeline->process(new ServerRequest());
    }

    public function testInvalidMiddleware()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe('some_invalid_func');
        $this->setExpectedException('InvalidArgumentException');
        $pipeline->process(new ServerRequest());
    }

    public function testMiddlewareViaConstructor()
    {
        $pipeline = new Pipeline(array(
            function (ServerRequestInterface $request, DelegateInterface $delegate) {
                $response = $delegate->process($request);
                $response->getBody()->write('!');
                return $response;
            },
            function () {
                $response = new Response();
                $response->getBody()->write('Hello');
                return $response;
            }
        ));
        $response = $pipeline->process(new ServerRequest());
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello!', (string)$response->getBody());
    }

    public function provideProcessRequests()
    {
        $uri = new Uri();
        return array(
            array(new ServerRequest('GET', $uri->withPath('/login'))),
            array(new ServerRequest('GET', $uri->withPath('/logout'))),
            array(new ServerRequest('GET', $uri->withPath('/dashboard'))),
            array(new ServerRequest('GET', $uri->withPath('/')))
        );
    }
}
