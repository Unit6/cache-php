<?php
/*
 * This file is part of the Cache package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unit6\Cache;

/**
 * Standard PSR-6 Exception
 *
 * Exception interface for all exceptions thrown by an Implementing Library.
 *
 * While caching is often an important part of application performance,
 * it should never be a critical part of application functionality.
 * Thus, an error in a cache system SHOULD NOT result in application failure.
 */
class InvalidArgumentException extends \InvalidArgumentException implements \Psr\Cache\InvalidArgumentException {}
