<?php
/**
 * Created by PhpStorm.
 * User: dan
 * Date: 3/31/15
 * Time: 10:20 AM
 */

class Cache {

    private $memCache = null;

    function __construct() {
        /*
         * Caching can be disabled in the config file if you aren't able to run a memcached instance.  In that case,
         * this class will still get used, but RTK will just pretend that it has a perpetually empty cache.
         */
        if (USE_MEMCACHED) {
            $this->memCache = new Memcache();
            $this->memCache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
        }
    }

    public function isCached($key) {
        $hashedKey = hash("sha1", MEMCACHED_PREFIX . $key);
        if (USE_MEMCACHED && $this->memCache->get($hashedKey) !== FALSE) {
            return true;
        } else {
            return false;
        }
    }

    public function save($key, $value, $duration) {
        $hashedKey = hash("sha1", MEMCACHED_PREFIX . $key);
        if (USE_MEMCACHED) {
            return $this->memCache->set($hashedKey,$value,0,$duration);
        } else {
            return true;
        }
    }

    public function load($key) {
        $hashedKey = hash("sha1", MEMCACHED_PREFIX . $key);
        if (USE_MEMCACHED) {
            return $this->memCache->get($hashedKey);
        } else {
            return false;
        }
    }

} 