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

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Interface for middleware pipeline implementations.
 *
 * This interface extends PSR-15 RequestHandlerInterface and adds the ability
 * to build a pipeline by adding middleware components. Implementations should
 * process requests through all registered middleware in the order they were added.
 *
 * @author Vaibhav Pandey <contact@vaibhavpandey.com>
 */
interface PipelineInterface extends RequestHandlerInterface
{
    /**
     * Adds a middleware component to the pipeline.
     *
     * The middleware will be added to the end of the pipeline and will be
     * executed after all previously added middleware. This method should support
     * method chaining for fluent interface usage.
     *
     * @param  MiddlewareInterface|callable  $middleware  The middleware to add.
     *                                                    Can be a MiddlewareInterface instance
     *                                                    or a callable with signature:
     *                                                    (ServerRequestInterface, RequestHandlerInterface): ResponseInterface
     * @return static Returns self for method chaining
     */
    public function pipe(MiddlewareInterface|callable $middleware): static;
}
