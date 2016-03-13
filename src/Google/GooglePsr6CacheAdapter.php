<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Google_Client;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The psr6 cache adapter for the google api client.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GooglePsr6CacheAdapter extends \Google_Cache_Abstract
{
    /**
     * @var CacheItemPoolInterface
     */
    private $psr6Cache;

    /**
     * GooglePsr6CacheAdapter constructor.
     *
     * @param Google_Client          $client
     * @param CacheItemPoolInterface $psr6Cache
     */
    public function __construct(Google_Client $client, CacheItemPoolInterface $psr6Cache = null)
    {
        $this->psr6Cache = $psr6Cache;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getPsr6Cache() : CacheItemPoolInterface
    {
        return $this->psr6Cache;
    }

    /**
     * Retrieves the data for the given key, or false if they
     * key is unknown or expired
     *
     * @param String      $key        The key who's data to retrieve
     * @param boolean|int $expiration Expiration time in seconds
     *
     * @return bool|mixed
     */
    public function get($key, $expiration = false)
    {
        $item = $this->psr6Cache->getItem($key);

        return $item->isHit() ? $item->get() : false;
    }

    /**
     * Store the key => $value set. The $value is serialized
     * by this function so can be of any type
     *
     * @param string $key   Key of the data
     * @param string $value data
     */
    public function set($key, $value)
    {
        $item = $this->psr6Cache->getItem($key);
        $item->set($value);

        $this->psr6Cache->save($item);
    }

    /**
     * Removes the key/data pair for the given $key
     *
     * @param String $key
     */
    public function delete($key)
    {
        $this->psr6Cache->deleteItem($key);
    }
}