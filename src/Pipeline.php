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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Pipeline implementation for processing HTTP requests through middleware.
 *
 * This class implements a middleware pipeline pattern based on PSR-15 specification.
 * It allows chaining multiple middleware components that can process and modify
 * HTTP requests and responses in sequence.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
final class Pipeline implements PipelineInterface
{
    /**
     * Array of middleware components to process.
     *
     * Each middleware can be either a MiddlewareInterface instance or a callable
     * with the signature: (ServerRequestInterface, RequestHandlerInterface): ResponseInterface
     *
     * @var array<int, MiddlewareInterface|callable>
     */
    private array $middleware = [];

    /**
     * Current position in the middleware stack during request processing.
     */
    private int $position = 0;

    /**
     * Creates a new pipeline instance.
     *
     * Optionally accepts an array of middleware to initialize the pipeline.
     * Each middleware will be added to the pipeline in the order provided.
     *
     * @param  array<int, MiddlewareInterface|callable>  $middleware  Optional array of middleware to add
     */
    public function __construct(array $middleware = [])
    {
        foreach ($middleware as $item) {
            $this->pipe($item);
        }
    }

    /**
     * Handles a server request and returns a response.
     *
     * Processes the request through the middleware pipeline. Each middleware
     * in the pipeline is executed in the order they were added. The middleware
     * can either return a response directly or delegate to the next middleware
     * in the chain.
     *
     * @param  ServerRequestInterface  $request  The HTTP request to process
     * @return ResponseInterface The HTTP response
     *
     * @throws \RuntimeException If the pipeline ends without returning a response
     * @throws \InvalidArgumentException If an invalid middleware type is encountered
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! isset($this->middleware[$this->position])) {
            throw new \RuntimeException(
                'Pipeline ended without returning any Psr\\Http\\Message\\ResponseInterface'
            );
        }

        $middleware = $this->middleware[$this->position];
        $next = clone $this;
        $next->position++;

        return match (true) {
            $middleware instanceof MiddlewareInterface => $middleware->process($request, $next),
            \is_callable($middleware) => $middleware($request, $next),
            default => throw new \InvalidArgumentException(
                \sprintf(
                    "Middleware must either be an instance of '%s' or a valid callable; '%s' given",
                    MiddlewareInterface::class,
                    \is_object($middleware) ? $middleware::class : \gettype($middleware)
                )
            ),
        };
    }

    /**
     * Adds a middleware component to the pipeline.
     *
     * The middleware will be added to the end of the pipeline and will be
     * executed after all previously added middleware. This method supports
     * method chaining for fluent interface usage.
     *
     * @param  MiddlewareInterface|callable  $middleware  The middleware to add.
     *                                                    Can be a MiddlewareInterface instance
     *                                                    or a callable with signature:
     *                                                    (ServerRequestInterface, RequestHandlerInterface): ResponseInterface
     * @return static Returns self for method chaining
     */
    public function pipe(MiddlewareInterface|callable $middleware): static
    {
        $this->middleware[] = $middleware;

        return $this;
    }
}
