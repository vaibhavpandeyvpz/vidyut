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

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Interface PipelineInterface
 * @package Vidyut
 */
interface PipelineInterface extends RequestHandlerInterface
{
    /**
     * @param MiddlewareInterface|callable $middleware
     * @return static
     */
    public function pipe($middleware);
}
