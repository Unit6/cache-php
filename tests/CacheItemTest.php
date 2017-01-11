<?php
/*
 * This file is part of the Cache package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Unit6\Cache;

/**
 * Test Cache Item
 *
 * Check for correct operation of the standard features.
 */
class CacheItemTest extends PHPUnit_Framework_TestCase
{
    private $item;

    public function setUp()
    {
    }

    public function tearDown()
    {
        unset($this->item);
    }

    public function testChangeCacheItemKey()
    {
        $key1 = uniqid();

        $item = new Cache\Item($key1, 'foobar');

        $this->assertEquals($item->getKey(), $key1);

        $key2 = uniqid();

        $item->setKey($key2);

        $this->assertNotEquals($item->getKey(), $key1);
        $this->assertEquals($item->getKey(), $key2);
    }

    public function testCreateItemWithCallback()
    {
        $value = uniqid();

        $callback = function () use ($value) {
            return [true, $value];
        };

        $item = new Cache\Item('foobar', $callback);

        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());

        return $item;
    }

    /**
     * @depends testCreateItemWithCallback
     */
    public function testChangeCallbackValue(Cache\Item $item)
    {
        $value = uniqid();

        $callback = function () use ($value) {
            return [true, $value];
        };

        $item->setCallback($callback);

        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());

        return $item;
    }

    public function testCreateItemWithStringValue()
    {
        $value = uniqid();

        $item = new Cache\Item('foobar', $value);

        $this->assertTrue($item->isHit());
        $this->assertEquals($value, $item->get());

        return $item;
    }

    /**
     * @depends testCreateItemWithStringValue
     */
    public function testAddExpirationDate(Cache\Item $item)
    {
        $ttl = 5;
        $expected = time() + $ttl;

        $dt = new DateTime(sprintf('+%sseconds', $ttl));
        $item->expiresAt($dt);
        $this->assertInstanceOf('DateTimeInterface', $item->getExpirationDate());
        $expiry = $item->getExpirationDate();
        $this->assertEquals($expiry->getTimestamp(), $expected);
    }

    /**
     * @depends testCreateItemWithStringValue
     */
    public function testExpirationDateInFuture(Cache\Item $item)
    {
        $ttl = 5;
        $item->expiresAfter($ttl);
        $this->assertTrue($item->isHit());

        return $item;
    }

    /**
     * @depends testExpirationDateInFuture
     */
    public function testExpirationDateInFutureExpired(Cache\Item $item)
    {
        $expiry = $item->getExpirationDate();
        sleep($expiry->getTimestamp() - time());
        $this->assertFalse($item->isHit());
    }
}