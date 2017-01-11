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

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Testing PSR-6 Compliance
 *
 * Check for compatibility with PSR-6 CacheItemInterface and CacheItemPoolInterface
 */
class CachePSR6ComplianceTest extends PHPUnit_Framework_TestCase
{
    private $cache;
    private $item;

    public function setUp()
    {
        $this->item = new Cache\Item(CACHE_KEY, CACHE_VALUE);
        $this->cache = new Cache\File(CACHE_PATH);
    }

    public function tearDown()
    {
        $this->cache->clear();

        unset($this->cache);
        unset($this->item);
    }

    public function testItemExpiryImplementsDateTimeInterface()
    {
        $time = 5;
        $dt = new DateTime(sprintf('+%sseconds', $time));
        $this->item->expiresAt($dt);
        $this->assertInstanceOf('DateTimeInterface', $this->item->getExpirationDate());
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testThrowExceptionOnEmptyKey()
    {
        $this->cache->getItem('');
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testThrowExceptionOnNonStringKey()
    {
        $this->cache->getItem([]);
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testThrowExceptionOnKeyWithReservedChars()
    {
        $this->cache->getItem('{}');
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testThrowExceptionOnKeyWithInvalidChars()
    {
        $this->cache->getItem('##');
    }

    public function testExceptionImplementsPSR6Interfaces()
    {
        $e = new Cache\InvalidArgumentException();

        $this->assertInstanceOf('Psr\Cache\InvalidArgumentException', $e);
        $this->assertInstanceOf('Psr\Cache\CacheException', $e);
    }

    public function testClassImplementsCacheItemPoolInterface()
    {
        $this->assertInstanceOf('Psr\Cache\CacheItemPoolInterface', $this->cache);
        $this->assertContains('Psr\Cache\CacheItemPoolInterface', array_keys(class_implements($this->cache)));
    }

    public function testClassImplementsCacheItemInterface()
    {
        $this->assertInstanceOf('Psr\Cache\CacheItemInterface', $this->item);
        $this->assertContains('Psr\Cache\CacheItemInterface', array_keys(class_implements($this->item)));
    }
}