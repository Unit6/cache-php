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

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;

use Psr\Cache\CacheItemInterface;

/**
 * Cache Item Class
 *
 * An Item represents a single key/value pair within a Pool.
 * The key is the primary unique identifier for an Item and
 * MUST be immutable. The Value MAY be changed at any time.
 */
class Item implements CacheItemInterface
{
    /**
     * Cache Item Key
     *
     * @var string
     */
    protected $key;

    /**
     * Cache Item Callback
     *
     * @var callable
     */
    protected $callback;

    /**
     * Cache Item Value
     *
     * @var mixed
     */
    private $value;

    /**
     *
     *
     * @var DateTimeInterface|null
     */
    private $expirationDate = null;

    /**
     * Determine whether cache item has a value already set.
     *
     * @var bool
     */
    private $hasValue = false;

    /**
     * Setup Cache Item
     *
     * Configure the cache item
     *
     * @param string $key   Cache item identifier.
     * @param mixed  $value Cache item value or callable for retrieving the item from the persistence layer.
     *
     * @return void
     */
    public function __construct($key, $value = null)
    {
        $this->setKey($key);

        if (is_callable($value)) {
            $this->setCallback($value);
        } else {
            $this->set($value);
        }
    }

    /**
     * Set the cache item key.
     *
     * @param string $key Cache item identifier.
     *
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Set the cache callback.
     *
     * @param callable $callback Cache persistence callback.
     *
     * @return void
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {
        if ( ! $this->isHit()) {
            return;
        }

        return $this->value;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit()
    {
        // Attempt to initialize if callable function exists.
        if (is_callable($this->callback)) {
            list($this->hasValue, $this->value) = call_user_func($this->callback);
            $this->callback = null;
        }

        if ( ! $this->hasValue) {
            return false;
        }

        if (null !== $this->expirationDate) {
            return ($this->expirationDate > new DateTime());
        }

        return true;
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {
        $this->value = $value;
        $this->hasValue = true;
        $this->callback = null;

        return $this;
    }

    /**
     * Get Expiration Date Object
     *
     * The date and time when the object expires.
     *
     * @return DateTimeInterface
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof DateTimeInterface) {
            $this->expirationDate = clone $expiration;
        } else {
            $this->expirationDate = $expiration;
        }

        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|DateInterval $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time = null)
    {
        if (null === $time) {
            $this->expirationDate = null;
        } elseif ($time instanceof DateInterval) {
            $this->expirationDate = new DateTime();
            $this->expirationDate->add($time);
        } elseif (is_integer($time)) {
            $this->expirationDate = new DateTime(sprintf('+%sseconds', $time));
        }

        return $this;
    }
}