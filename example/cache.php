<?php
/*
 * This file is part of the Cache package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require realpath(dirname(__FILE__) . '/../autoload.php');
require realpath(dirname(__FILE__) . '/../vendor/autoload.php');

use Unit6\Cache;

$directory = realpath(dirname(__FILE__) . '/storage');
$key = 'foobar';
$value = ['example.com', uniqid()];
$ttl = 5;

$cache = new Cache\File($directory);

#$item = new Cache\Item($key, $value);

#$item->expiresAfter($ttl);

#$cache->save($item);

var_dump($cache->getItem($key)); exit;