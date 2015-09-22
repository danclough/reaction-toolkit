<?php

/**
 * Provides caching functions to other classes to reduce database load.
 */
class CacheManager {

    /**
     * @var Memcached $cache A handle used to connect to the configured cache.
     */
    private $cache = null;

    public function __construct($persistent = true) {
        /*
         * Caching can be disabled in the config file if you aren't able to run
         * a memcached instance.  In that case this class will still get used,
         * but we will pretend that we have a perpetually empty cache.
         */
        if (defined("USE_MEMCACHED") && USE_MEMCACHED != false) {
            $servers = null;
            if ($persistent === true) {
                /*
                 * Since prefix should be unique across RTK installations, we can
                 * use the server hostname + port + prefix as a persistent ID.
                 * Hostname + port are included because otherwise changing the
                 * memcached server would NOT change the persistent connection,
                 * and you'd end up using the old server.
                 */
                $persistentID = MEMCACHED_HOST . ":" . MEMCACHED_PORT . "_" . MEMCACHED_PREFIX;
                $this->cache = new Memcached($persistentID);
                $serverList = $this->cache->getServerList();
                foreach ($serverList as $entry) {
                    $servers[] = $entry['host'] . ":" . $entry['port'];
                }
            } else {
                //Not running Memcached in persistent mode.
                $this->cache = new Memcached();
            }
            if ($persistent !== true || !is_array($servers) || !in_array(MEMCACHED_HOST . ":" . MEMCACHED_PORT, $servers)) {
                $this->cache->addServer(MEMCACHED_HOST, MEMCACHED_PORT);
            }
        }
    }

    public function __destruct() {
        //Terminate active memcached connection.
        if (isset($this->cache)) {
            $this->cache->quit();
        }
    }

    /**
     * Checks the cache for the presence of a given key.
     * 
     * Memcached does not have a built-in method to check if a key is cached,
     * so internally this method just calls the load() function and checks the
     * result code via Memcached::getResultCode().
     * 
     * @param string $key The key to check.
     * @return boolean TRUE if the requested key is cached, FALSE otherwise.
     */
    public function isCached($key) {
        if (isset($this->cache)) {
            $result = $this->load($key);
            if ($this->cache->getResultCode() == Memcached::RES_SUCCESS) {
                return true;
            }
        }
        return false;
    }

    /**
     * Stores data in the cache.
     * 
     * @param string $key The key under which to save the data.
     * @param mixed $data Any object or value that can be serialized.
     * @param int $duration How long to cache the data in seconds, or 0 for indefinite.
     * @return boolean Indicates whether the value was cached successfully.
     */
    public function save($key, $data, $duration) {
        if (isset($this->cache)) {
            $hashedKey = hash("sha1", MEMCACHED_PREFIX . $key);
            return $this->cache->set($hashedKey, $data, $duration);
        }
        return true;
    }

    /**
     * Retrieves data from the cache.
     * 
     * @param type $key The key under which the data was stored.
     * @return mixed The requested object or value, or false if not found.
     */
    public function load($key) {
        if (isset($this->cache)) {
            $hashedKey = hash("sha1", MEMCACHED_PREFIX . $key);
            return $this->cache->get($hashedKey);
        }
        return false;
    }

}
