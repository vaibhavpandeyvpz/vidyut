<?php

/*
 * This file is part of vaibhavpandeyvpz/vidyut package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Vidyut;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sandesh\ResponseFactory;
use Sandesh\ServerRequest;
use Sandesh\Uri;

/**
 * Test suite for Pipeline class.
 *
 * Tests cover all aspects of the middleware pipeline including:
 * - Basic request processing
 * - Middleware execution order
 * - Different middleware types (callables, MiddlewareInterface)
 * - Request/response modification
 * - Error handling and edge cases
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class PipelineTest extends TestCase
{
    /**
     * Tests basic pipeline processing with different request paths.
     *
     * @param  ServerRequestInterface  $request  The request to process
     */
    #[DataProvider('provideProcessRequests')]
    public function test_process(ServerRequestInterface $request): void
    {
        $pipeline = new Pipeline;
        $pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
            if ($request->getUri()->getPath() === '/login') {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Login');

                return $response;
            }

            return $delegate->handle($request);
        });
        $pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
            if ($request->getUri()->getPath() === '/logout') {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Logout');

                return $response;
            }

            return $delegate->handle($request);
        });
        $pipeline->pipe(function (): ResponseInterface {
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write('This URL does not exist.');

            return $response->withStatus(404);
        });

        $response = $pipeline->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        match ($request->getUri()->getPath()) {
            '/login' => $this->assertLoginResponse($response),
            '/logout' => $this->assertLogoutResponse($response),
            default => $this->assertNotFoundResponse($response),
        };
    }

    /**
     * Tests that an exception is thrown when handling a request with no middleware.
     */
    public function test_empty_middleware(): void
    {
        $pipeline = new Pipeline;
        $this->expectException(\RuntimeException::class);
        $pipeline->handle(new ServerRequest);
    }

    /**
     * Tests that an exception is thrown when invalid middleware is encountered.
     *
     * Uses reflection to bypass type system and test runtime validation.
     */
    public function test_invalid_middleware(): void
    {
        $pipeline = new Pipeline;
        // Use reflection to bypass type system and test runtime validation
        $reflection = new \ReflectionClass($pipeline);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);
        $property->setValue($pipeline, [new \stdClass]);

        $this->expectException(\InvalidArgumentException::class);
        $pipeline->handle(new ServerRequest);
    }

    /**
     * Tests that middleware can be passed via constructor.
     */
    public function test_middleware_via_constructor(): void
    {
        $pipeline = new Pipeline([
            function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
                $response = $delegate->handle($request);
                $response->getBody()->write('!');

                return $response;
            },
            function (): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Hello');

                return $response;
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello!', (string) $response->getBody());
    }

    /**
     * Tests that an empty array constructor behaves the same as no middleware.
     */
    public function test_empty_array_constructor(): void
    {
        $pipeline = new Pipeline([]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pipeline ended without returning any');
        $pipeline->handle(new ServerRequest);
    }

    /**
     * Tests that pipe() method returns the pipeline instance for chaining.
     */
    public function test_pipe_method_chaining(): void
    {
        $pipeline = new Pipeline;
        $result = $pipeline
            ->pipe(function (): ResponseInterface {
                return (new ResponseFactory)->createResponse();
            })
            ->pipe(function (): ResponseInterface {
                return (new ResponseFactory)->createResponse();
            });

        $this->assertSame($pipeline, $result);
        $this->assertInstanceOf(Pipeline::class, $result);
    }

    /**
     * Tests that middleware executes in the order they were added.
     */
    public function test_middleware_execution_order(): void
    {
        $executionOrder = [];
        $pipeline = new Pipeline;
        $pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate) use (&$executionOrder): ResponseInterface {
            $executionOrder[] = 1;
            $response = $delegate->handle($request);
            $response->getBody()->write('1');

            return $response;
        });
        $pipeline->pipe(function (ServerRequestInterface $request, RequestHandlerInterface $delegate) use (&$executionOrder): ResponseInterface {
            $executionOrder[] = 2;
            $response = $delegate->handle($request);
            $response->getBody()->write('2');

            return $response;
        });
        $pipeline->pipe(function () use (&$executionOrder): ResponseInterface {
            $executionOrder[] = 3;
            $response = (new ResponseFactory)->createResponse();
            $response->getBody()->write('3');

            return $response;
        });

        $response = $pipeline->handle(new ServerRequest);
        $this->assertEquals([1, 2, 3], $executionOrder);
        $this->assertEquals('321', (string) $response->getBody());
    }

    /**
     * Tests that MiddlewareInterface instances work correctly.
     */
    public function test_middleware_with_middleware_interface(): void
    {
        $middleware = new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);
                $response->getBody()->write('Middleware');

                return $response;
            }
        };

        $pipeline = new Pipeline([
            $middleware,
            function (): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Base');

                return $response;
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertEquals('BaseMiddleware', (string) $response->getBody());
    }

    /**
     * Tests that MiddlewareInterface instances and callables can be mixed.
     */
    public function test_mixed_middleware_interface_and_callables(): void
    {
        $middleware1 = new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = $handler->handle($request);
                $response->getBody()->write('A');

                return $response;
            }
        };

        $middleware2 = function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
            $response = $delegate->handle($request);
            $response->getBody()->write('B');

            return $response;
        };

        $pipeline = new Pipeline([
            $middleware1,
            $middleware2,
            function (): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('C');

                return $response;
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertEquals('CBA', (string) $response->getBody());
    }

    /**
     * Tests that middleware can modify the request before passing to next handler.
     */
    public function test_middleware_modifies_request(): void
    {
        $pipeline = new Pipeline([
            function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
                $modifiedRequest = $request->withAttribute('custom', 'value');

                return $delegate->handle($modifiedRequest);
            },
            function (ServerRequestInterface $request): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write($request->getAttribute('custom', ''));

                return $response;
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertEquals('value', (string) $response->getBody());
    }

    /**
     * Tests that middleware can modify the response after receiving from next handler.
     */
    public function test_middleware_modifies_response(): void
    {
        $pipeline = new Pipeline([
            function (ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
                $response = $delegate->handle($request);

                return $response->withHeader('X-Custom', 'Header');
            },
            function (): ResponseInterface {
                return (new ResponseFactory)->createResponse();
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertTrue($response->hasHeader('X-Custom'));
        $this->assertEquals('Header', $response->getHeaderLine('X-Custom'));
    }

    /**
     * Tests that middleware can terminate early without calling the delegate.
     */
    public function test_middleware_early_termination(): void
    {
        $executed = false;
        $pipeline = new Pipeline([
            function (): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Early');

                return $response;
            },
            function () use (&$executed): ResponseInterface {
                $executed = true;

                return (new ResponseFactory)->createResponse();
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertFalse($executed);
        $this->assertEquals('Early', (string) $response->getBody());
    }

    /**
     * Tests that the pipeline can handle multiple requests sequentially.
     */
    public function test_multiple_handle_calls(): void
    {
        $pipeline = new Pipeline([
            function (): ResponseInterface {
                return (new ResponseFactory)->createResponse(200);
            },
        ]);

        $response1 = $pipeline->handle(new ServerRequest);
        $response2 = $pipeline->handle(new ServerRequest);

        $this->assertInstanceOf(ResponseInterface::class, $response1);
        $this->assertInstanceOf(ResponseInterface::class, $response2);
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * Tests that the exception message contains the expected content.
     */
    public function test_exception_message_content(): void
    {
        $pipeline = new Pipeline;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pipeline ended without returning any Psr\\Http\\Message\\ResponseInterface');
        $pipeline->handle(new ServerRequest);
    }

    /**
     * Tests that invalid middleware throws an exception with a descriptive message.
     */
    public function test_invalid_middleware_exception_message(): void
    {
        $pipeline = new Pipeline;
        $reflection = new \ReflectionClass($pipeline);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);
        $invalidMiddleware = new \stdClass;
        $property->setValue($pipeline, [$invalidMiddleware]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware must either be an instance of');
        $this->expectExceptionMessage(\stdClass::class);
        $pipeline->handle(new ServerRequest);
    }

    /**
     * Tests that invalid middleware with non-object types throws appropriate exception.
     */
    public function test_invalid_middleware_with_non_object(): void
    {
        $pipeline = new Pipeline;
        $reflection = new \ReflectionClass($pipeline);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);
        $property->setValue($pipeline, [123]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('integer');
        $pipeline->handle(new ServerRequest);
    }

    /**
     * Tests that the delegate passed to middleware is a Pipeline instance (cloned).
     */
    public function test_delegate_is_pipeline_instance(): void
    {
        $delegateReceived = null;
        $pipeline = new Pipeline([
            function (ServerRequestInterface $request, RequestHandlerInterface $delegate) use (&$delegateReceived): ResponseInterface {
                $delegateReceived = $delegate;

                return $delegate->handle($request);
            },
            function (): ResponseInterface {
                return (new ResponseFactory)->createResponse();
            },
        ]);

        $pipeline->handle(new ServerRequest);
        $this->assertInstanceOf(Pipeline::class, $delegateReceived);
        $this->assertNotSame($pipeline, $delegateReceived);
    }

    /**
     * Tests that array callables (object method) work as middleware.
     */
    public function test_array_callable(): void
    {
        $callable = new class
        {
            public function handle(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
            {
                $response = $delegate->handle($request);
                $response->getBody()->write('ArrayCallable');

                return $response;
            }
        };

        $pipeline = new Pipeline([
            [$callable, 'handle'],
            function (): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Base');

                return $response;
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertEquals('BaseArrayCallable', (string) $response->getBody());
    }

    /**
     * Tests that static method callables work as middleware.
     */
    public function test_static_callable(): void
    {
        $pipeline = new Pipeline([
            [self::class, 'staticMiddleware'],
            function (): ResponseInterface {
                $response = (new ResponseFactory)->createResponse();
                $response->getBody()->write('Base');

                return $response;
            },
        ]);

        $response = $pipeline->handle(new ServerRequest);
        $this->assertEquals('BaseStatic', (string) $response->getBody());
    }

    /**
     * Static middleware method used for testing static callables.
     *
     * @param  ServerRequestInterface  $request  The HTTP request
     * @param  RequestHandlerInterface  $delegate  The next handler in the pipeline
     * @return ResponseInterface The HTTP response
     */
    public static function staticMiddleware(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $response = $delegate->handle($request);
        $response->getBody()->write('Static');

        return $response;
    }

    /**
     * Provides test data for process tests with different request paths.
     *
     * @return array<int, array<int, ServerRequestInterface>> Array of test requests
     */
    public static function provideProcessRequests(): array
    {
        $uri = new Uri;

        return [
            [new ServerRequest('GET', $uri->withPath('/login'))],
            [new ServerRequest('GET', $uri->withPath('/logout'))],
            [new ServerRequest('GET', $uri->withPath('/dashboard'))],
            [new ServerRequest('GET', $uri->withPath('/'))],
        ];
    }

    /**
     * Asserts that a response matches the expected login response.
     *
     * @param  ResponseInterface  $response  The response to assert
     */
    private function assertLoginResponse(ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Login', (string) $response->getBody());
    }

    /**
     * Asserts that a response matches the expected logout response.
     *
     * @param  ResponseInterface  $response  The response to assert
     */
    private function assertLogoutResponse(ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Logout', (string) $response->getBody());
    }

    /**
     * Asserts that a response matches the expected not found response.
     *
     * @param  ResponseInterface  $response  The response to assert
     */
    private function assertNotFoundResponse(ResponseInterface $response): void
    {
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('This URL does not exist.', (string) $response->getBody());
    }
}
