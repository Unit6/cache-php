# Cache

A simple [PSR-6 compliant](http://www.php-fig.org/psr/psr-6/) cache class for PHP.

```php
use Unit6\Cache;

$directory = realpath(dirname(__FILE__) . '/storage');
$key = 'foobar';
$value = ['example.com', uniqid()];
$ttl = 5;

$cache = new Cache\File($directory);

$item = new Cache\Item($key, $value);

$item->expiresAfter($ttl);

$cache->save($item);

var_dump($cache->getItem($key));
```

### License

This project is licensed under the MIT license -- see the `LICENSE.txt` for the full license details.

### Acknowledgements

Some inspiration has been taken from the following projects:

- [frqnck/apix-cache](https://github.com/frqnck/apix-cache)
- [gpupo/cache](https://github.com/gpupo/cache)
- [php-cache/cache](https://github.com/php-cache/cache)