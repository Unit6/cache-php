<?php
/*
 * This file is part of the Cache package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// set the default timezone
date_default_timezone_set('UTC');

require realpath(__DIR__ . '/../vendor/autoload.php');

define('CACHE_PATH', realpath(dirname(__FILE__) . '/storage'));
define('CACHE_KEY', 'foobar');
define('CACHE_VALUE', uniqid());
