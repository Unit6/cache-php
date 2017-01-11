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
 * Test Cache
 *
 * Check for correct operation of the standard features.
 */
class CacheFileTest extends PHPUnit_Framework_TestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new Cache\File(CACHE_PATH);
    }

    public function tearDown()
    {
        $this->cache->clear();

        unset($this->cache);
    }

    public function testSaveCacheItem()
    {
        $this->assertInstanceOf('Unit6\Cache\PoolInterface', $this->cache);

        $value = uniqid();

        $item = new Cache\Item('foobar', $value);

        #$this->assertEquals($value, $item->get());

        $result = $this->cache->save($item);

        $this->assertTrue($result);

        return $item->getKey();
    }

    /**
     * @depends testSaveCacheItem
     */
    public function testGetCachedItems($key)
    {
        $result = $this->cache->getItems([$key]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($key, $result);
    }

    public function testDeferredSaveOfCacheItem()
    {
        $value = uniqid();

        $item = new Cache\Item('raboof', $value);
        $cache = new Cache\File(CACHE_PATH);

        #$this->assertEquals($value, $item->get());

        $result = $cache->saveDeferred($item);

        $this->assertTrue($result);

        return [$cache, $item];
    }

    /**
     * @depends testDeferredSaveOfCacheItem
     */
    public function testCommitDeferredSave(array $deferred)
    {
        list($cache, $item) = $deferred;

        $path = $cache->getPath() . $item->getKey() . '.json';

        $this->assertFalse(file_exists($path));

        $result = $cache->commit();

        $this->assertTrue($result);

        $this->assertTrue(file_exists($path));
    }
}