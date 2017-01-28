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
use Interop\Http\ServerMiddleware\MiddlewareInterface;

/**
 * Interface PipelineInterface
 * @package Vidyut
 */
interface PipelineInterface extends DelegateInterface
{
    /**
     * @param MiddlewareInterface|callable $middleware
     * @return static
     */
    public function pipe($middleware);
}
