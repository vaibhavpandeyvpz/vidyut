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
use Psr\Http\Server\MiddlewareInterface;

/**
 * Class Pipeline
 * @package Vidyut
 */
class Pipeline implements PipelineInterface
{
    /**
     * @var array
     */
    protected $middleware = [];

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * Pipeline constructor.
     * @param array $middleware
     */
    public function __construct(array $middleware = [])
    {
        array_walk($middleware, [$this, 'pipe']);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->middleware[$this->position])) {
            throw new \RuntimeException('Pipeline ended without returning any Psr\\Http\\Message\\ResponseInterface');
        }
        $middleware = $this->middleware[$this->position];
        $next = clone $this;
        $next->position++;
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $next);
        } elseif (is_callable($middleware)) {
            return call_user_func($middleware, $request, $next);
        }
        throw new \InvalidArgumentException(sprintf(
            "Middleware must either be an instance of '%s' or a valid callable; '%s' given",
            MiddlewareInterface::class,
            is_object($middleware) ? get_class($middleware) : gettype($middleware)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function pipe($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }
}
