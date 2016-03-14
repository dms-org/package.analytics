<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The write-only cache item pool decorator.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class WriteOnlyCachePoolDecorator implements CacheItemPoolInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * WriteOnlyCachePoolDecorator constructor.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    protected function cacheItemDecorator(CacheItemInterface $cacheItem)
    {
        return new class ($cacheItem) implements CacheItemInterface
        {
            /**
             * @var CacheItemInterface
             */
            protected $cacheItem;

            /**
             * @param CacheItemInterface $cacheItem
             */
            public function __construct(CacheItemInterface $cacheItem)
            {
                $this->cacheItem = $cacheItem;
            }

            /**
             * @return CacheItemInterface
             */
            public function getInnerCacheItem() : CacheItemInterface
            {
                return $this->cacheItem;
            }

            /**
             * @inheritDoc
             */
            public function getKey()
            {
                return $this->cacheItem->getKey();
            }

            /**
             * @inheritDoc
             */
            public function get()
            {
                return $this->cacheItem->get();
            }

            /**
             * @inheritDoc
             */
            public function isHit()
            {
                return false;
            }

            /**
             * @inheritDoc
             */
            public function set($value)
            {
                $this->cacheItem->set($value);
            }

            /**
             * @inheritDoc
             */
            public function expiresAt($expiration)
            {
                $this->cacheItem->expiresAt($expiration);
            }

            /**
             * @inheritDoc
             */
            public function expiresAfter($time)
            {
                $this->cacheItem->expiresAfter($time);
            }
        };
    }

    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        return $this->cacheItemDecorator($this->cache->getItem($key));
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = array())
    {
        $items = [];

        foreach ($this->cache->getItems($keys) as $key => $item) {
            $items[$key] = $this->cacheItemDecorator($item);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->cache->clear();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys);
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        return $this->cache->save($this->undecorate($item));
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->cache->saveDeferred($this->undecorate($item));
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return $this->cache->commit();
    }

    private function undecorate(CacheItemInterface $item) : CacheItemInterface
    {
        if (method_exists($item, 'getInnerCacheItem')) {
            return $item->getInnerCacheItem();
        } else {
            return $item;
        }
    }

}