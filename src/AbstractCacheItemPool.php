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

use DateTime;
use DateTimeZone;
use DateTimeInterface;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Abstract Cache Pool Class
 *
 * The Pool represents a collection of items in a caching system.
 * The pool is a logical Repository of all items it contains. All
 * cacheable items are retrieved from the Pool as an Item object,
 * and all interaction with the whole universe of cached objects
 * happens through the Pool.
 */
abstract class AbstractCacheItemPool implements PoolInterface
{
    /**
     * Set the timezone for log files
     *
     * @var DateTimeZone
     */
    private $timezone;

    /**
     * Deferred cache items
     *
     * Collection of items that may not have been
     * persisted immediately by the pool.
     *
     * @var CacheItemInterface[]
     */
    protected $deferred = [];

    /**
     * Attempt to persist deferred items before we destruct.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->commit();
    }

    /**
     * Set the cache timezone.
     *
     * @param string $tz Timezone string
     */
    public function setTimezone($tz = NULL)
    {
        if ( ! $tz ) {
            $tz = $this->options['timezone'] ?: date_default_timezone_get() ?: 'UTC';
        }

        $this->timezone = new DateTimeZone($tz);
    }

    /**
     * Fetch an object from the cache implementation.
     *
     * @param string $key
     *
     * @return array with [isHit, value]
     */
    abstract protected function fetchObjectFromCache($key);

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        // Attempt to validate the cache item key and throw an exception if invalid.
        static::validateKey($key);

        // Does key exist in deferred list.
        if (isset($this->deferred[$key])) {
            $item = $this->deferred[$key];
            return is_object($item) ? clone $item : $item;
        }

        // Persistence callback for retrieval.
        $callback = function () use ($key) {
            return $this->fetchObjectFromCache($key);
        };

        return new Item($key, $callback);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = [])
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *    The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *  True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * Flush all objects from cache.
     *
     * @return bool false if error
     */
    abstract protected function clearAllObjectsFromCache();

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        // Clear the deferred items
        $this->deferred = [];

        return $this->clearAllObjectsFromCache();
    }

    /**
     * Remove one object from cache.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract protected function clearOneObjectFromCache($key);

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        // Attempt to validate the cache item key and throw an exception if invalid.
        static::validateKey($key);

        $deleted = true;

        // Delete form deferred list.
        if (isset($this->deferred[$key])) {
            unset($this->deferred[$key]);
            if (isset($this->deferred[$key])) {
                $deleted = false;
            }
        }

        if ( ! $this->clearOneObjectFromCache($key)) {
            $deleted = false;
        }

        return $deleted;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        $deleted = true;

        foreach ($keys as $key) {
            if ( ! $this->deleteItem($key)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    /**
     * Persist cache item in cache.
     *
     * @param string    $key  Cache item identifier.
     * @param CacheItem $item Cache item to store.
     * @param int|null  $ttl  Time-to-Live in seconds from now.
     *
     * @return bool true if saved
     */
    abstract protected function storeItemInCache($key, Item $item, $ttl);

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        $key = $item->getKey();

        $expirationDate = $item->getExpirationDate();

        $ttl = null;

        if ($expirationDate instanceof DateTimeInterface) {
            $ttl = $expirationDate->getTimestamp() - time();
        }

        return $this->storeItemInCache($key, $item, $ttl);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $key = $item->getKey();
        $this->deferred[$key] = $item;
        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        $saved = true;

        foreach ($this->deferred as $item) {
            if ( ! $this->save($item)) {
                $saved = false;
            }
        }

        $this->deferred = [];

        return $saved;
    }

    /**
     * Filter Key
     *
     * A string of at least one character that uniquely identifies a cached item,
     * consisting of the characters A-Z, a-z, 0-9, _, and up to 64 characters.
     * The following characters are reserved for future extensions and MUST NOT
     * be used: {}()/\@:
     *
     * @param string $key Cache item identifier.
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected static function validateKey($key)
    {
        // Key must be a string.
        if ( ! is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given', gettype($key)));
        }

        // Key must have at least one character.
        if ( ! strlen($key)) {
            throw new InvalidArgumentException('Invalid key. Must be at least one character.');
        }

        // Key should not use reserved characters.
        if (preg_match('|[\{\}\(\)/\\\@\:]|', $key)) {
            throw new InvalidArgumentException(sprintf('Invalid key: "%s". The key contains one or more characters reserved for future extension: {}()/\@:', $key));
        }

        $tokens = '[a-zA-Z0-9_]';

        // Key should use a limited set of characters.
        if ( ! preg_match('|^' . $tokens . '+$|', $key)) {
            throw new InvalidArgumentException(sprintf('Invalid key "%s". Valid keys must match ' . $tokens . '.', $key));
        }
    }
}