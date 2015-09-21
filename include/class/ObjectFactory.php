<?php

/**
 * Creates an instance of a requested class and populates it with data from a
 * given ID in the database, or retrieves a copy of the object from the cache if
 * available.
 */
class ObjectFactory {

    /**
     * @var DatabaseManager $dbMgr A Database object for retrieving object data.
     */
    private $dbMgr = null;

    /**
     * @var CacheManager $cacheMgr Provides functions to cache query results.
     */
    private $cacheMgr = null;

    const SYSTEM = 1;
    const TYPE = 2;
    const REACTION = 3;

    public function __construct() {
        $this->dbMgr = new DatabaseManager(true);
        $this->cacheMgr = new CacheManager();
    }

    public function create($class, $id) {
        switch ($class) {
            case self::SYSTEM:
                return $this->createSystem($id);
            case self::TYPE:
                return $this->createType($id);
            case self::REACTION:
                return $this->createReaction($id);
            default:
                return false;
        }
    }

    private function createSystem($id) {
        $key = "SystemObject-$id";
        if ($this->cacheMgr->isCached($key)) {
            return $this->cacheMgr->load($key);
        } else {
            $object = new System($id);
            $this->cacheMgr->save($key, $object, 3600);
            return $object;
        }
    }

    private function createType($id) {
        $key = "TypeObject-$id";
        if ($this->cacheMgr->isCached($key)) {
            return $this->cacheMgr->load($key);
        } else {
            $object = new Type($id);
            $this->cacheMgr->save($key, $object, 3600);
            return $object;
        }
    }

    private function createReaction($id) {
        $key = "ReactionObject-$id";
        if ($this->cacheMgr->isCached($key)) {
            return $this->cacheMgr->load($key);
        } else {
            $object = new Reaction($id);
            $this->cacheMgr->save($key, $object, 3600);
            return $object;
        }
    }

}
