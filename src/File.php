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
 * File Cache Adapter
 *
 * Using the filesystem as a persistence layer.
 */
class File extends AbstractCacheItemPool
{
    /**
     * Cache directory path
     *
     * Directory used as storage engine.
     *
     * @var string
     */
    protected $directory;

    /**
     * Cache default options
     *
     * @var array
     */
    protected $options = [
        'directoryPermissions' => 0777,
        'serialization' => [ 'encode' => 'json_encode', 'decode' => 'json_decode'],
        #'serialization' => [ 'encode' => 'serialize',   'decode' => 'unserialize'],
        'extension' => 'json',
    ];

    /**
     * Setup File Path
     *
     * Configure a filesystem-based storage engine.
     *
     * @param string $directory Directory path to use for storage.
     * @param array  $options   List of parameters.
     *
     * @return void
     */
    public function __construct($directory, array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        $this->setPath($directory);
    }

    /**
     * Get the file path that the log is currently writing to
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set logging path directory.
     *
     * @param string $path
     *
     * @return void
     */
    public function setPath($directory)
    {
        // Strip trailing separator.
        $path = rtrim($directory, DIRECTORY_SEPARATOR);

        // Create directory if path doesn't exist
        if ( ! file_exists($path)) {
            mkdir($path, $this->options['directoryPermissions'], true);
        }

        // Append trailing directory separator.
        $this->path = $path . DIRECTORY_SEPARATOR;

        // Check path exists and is writable
        if ( ! is_dir($this->path) || ! is_writable($this->path)) {
            throw new RuntimeException(sprintf('The file could not be written to. Check permissions: %s', $this->path));
        }
    }

    /**
     * Persist cache item in cache.
     *
     * @param string             $key  Cache item identifier.
     * @param CacheItemInterface $item Cache item to store.
     * @param int|null           $ttl  Time-to-Live in seconds from now.
     *
     * @return bool true if saved
     */
    protected function storeItemInCache($key, Item $item, $ttl)
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }

        $data = [
            ($ttl === null ? null : time() + $ttl),
            $item->get(),
        ];

        $value = $this->encode($data);

        $bytes = file_put_contents($file, $value);

        return !!$bytes;
    }

    /**
     * Fetch an object from the cache implementation.
     *
     * @param string $key
     *
     * @return array with [isHit, value]
     */
    protected function fetchObjectFromCache($key)
    {
        $file = $this->getFilePath($key);
        if ( ! file_exists($file)) {
            return [false, null];
        }

        $value = file_get_contents($file);
        $data = $this->decode($value);

        if ($data[0] !== null && time() > $data[0]) {
            $this->clearOneObjectFromCache($key);
            return [false, null];
        }

        return [true, $data[1]];
    }

    /**
     * Flush all objects from cache.
     *
     * @return bool false if error
     */
    protected function clearAllObjectsFromCache()
    {
        // GLOB_MARK adds a slash to directories returned
        $files = glob( $this->getPath() . '*', GLOB_MARK );

        foreach ($files as $target) {
            if (is_file($target)) {
                @unlink($target);
            }
        }

        return true;
    }

    /**
     * Remove one object from cache.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function clearOneObjectFromCache($key)
    {
        try {
            $file = $this->getFilePath($key);
            return @unlink($file);
        } catch (FileNotFoundException $e) {
            return true;
        }
    }

    /**
     * @param string $key
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function getFilePath($key)
    {
        // Attempt to validate the cache item key and throw an exception if invalid.
        static::validateKey($key);

        return sprintf('%s/%s.%s', $this->getPath(), $key, $this->options['extension']);
    }

    /**
     * Encode the string
     *
     * @param mixed $value Cache value to serialize
     *
     * @return string
     */
    private function encode($value)
    {
        $fn = $this->options['serialization']['encode'];

        if ( ! is_callable($fn)) {
            $fn = 'serialize';
        }

        return call_user_func($fn, $value);
    }

    /**
     * Decode the string
     *
     * @param string $value Cache value to serialize
     *
     * @return array
     */
    private function decode($value)
    {
        $fn = $this->options['serialization']['decode'];

        if ( ! is_callable($fn)) {
            $fn = 'unserialize';
        }

        return call_user_func($fn, $value);
    }
}