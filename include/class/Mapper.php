<?php

/**
 * Class Mapper
 * Maps one thing to another.
 */
class Mapper {
    private $db, $dbPrefix, $memCache;

    public function __construct() {
        $this->dbPrefix=DATABASE_PREFIX;
        try {
            // Create database object
            $this->db = new PDO("mysql:host=" . DATABASE_HOST . ";dbname=" . DATABASE_NAME . ";charset=utf8", DATABASE_USERNAME, DATABASE_PASSWORD);
            // PDO Security and Error Handling settings
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Exception $e) {
            echo "Failed to connect to database.  " . $e->getMessage() . " (Code ".$e->getCode().")";
        }

        $this->memCache = NULL;
        if(USE_MEMCACHED) {
            $this->memCache = new Memcache();
            $this->memCache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
        }
    }

    public function getNumCycles($timeframe) {
        $numCycles = 0;
        switch ($timeframe) {
            case "d":
                $numCycles = 24;
                break;
            case "w":
                $numCycles = 168;
                break;
            case "m":
                $numCycles = 720;
                break;
        }
        return $numCycles;
    }

    public function mapTypeIDToReactionID($typeID,$isAlchemy) {
        $functionCall = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        $key = hash("md5", $functionCall);
        if (isset($this->memCache)) {
            $cachedResult = $this->memCache->get($key);
            if ($cachedResult !== FALSE) {
                return $cachedResult;
            }
        }
        if ($isAlchemy) {
            $sql = <<<EOT
SELECT reactionID
FROM {$this->dbPrefix}_reactions
WHERE typeID=:typeID AND reactionType=3;
EOT;
        } else {
            $sql = <<<EOT
SELECT reactionID
FROM {$this->dbPrefix}_reactions
WHERE typeID=:typeID AND reactionType <> 3;
EOT;
        }
        $query = $this->db->prepare($sql);
        $query->bindParam(":typeID",$typeID,PDO::PARAM_INT);

        $query->execute();
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if (isset($this->memCache)) {
                $this->memCache->set($key,$row['reactionID'],0,86400);
            }
            return $row['reactionID'];
        }
        return null;
    }

    public function mapTypeIDToItemName($typeID) {
        $functionCall = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        $key = hash("md5", $functionCall);

        if (isset($this->memCache)) {
            $cachedResult = $this->memCache->get($key);
            if ($cachedResult !== FALSE) {
                return $cachedResult;
            }
        }
        $sql = <<<EOT
SELECT itemName
FROM {$this->dbPrefix}_types
WHERE typeID=:typeID;
EOT;
        $query = $this->db->prepare($sql);
        $query->bindParam(":typeID",$typeID,PDO::PARAM_INT);

        $query->execute();
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if (isset($this->memCache)) {
                $this->memCache->set($key,$row['itemName'],0,86400);
            }
            return $row['itemName'];
        }
        return null;
    }

    public function mapSystemIDToSystemName($systemID) {
        $functionCall = __METHOD__."(".implode(",",func_get_args()).")";
        $key = hash("md5",$functionCall);

        if (isset($this->memCache)) {
            $cachedResult = $this->memCache->get($key);
            if ($cachedResult !== FALSE) {
                return $cachedResult;
            }
        }

        $sql = <<<EOT
SELECT systemName
FROM {$this->dbPrefix}_systems
WHERE systemID=:systemID;
EOT;
        $query = $this->db->prepare($sql);
        $query->bindParam(":systemID",$systemID,PDO::PARAM_INT);

        $query->execute();
        if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if (isset($this->memCache)) {
                $this->memCache->set($key,$row['systemName'],0,86400);
            }
            return $row['systemName'];
        }
        return null;
    }

    public function getAllReactionIDs($type = NULL)
    {
        $functionCall = __METHOD__."(".implode(",",func_get_args()).")";
        $key = hash("md5",$functionCall);

        if (isset($this->memCache)) {
            $cachedResult = $this->memCache->get($key);
            if ($cachedResult !== FALSE) {
                return $cachedResult;
            }
        }

        if(isset($type)) {
            $sql = <<<EOT
SELECT reactionID
FROM {$this->dbPrefix}_reactions
WHERE reactionType=:type;
EOT;
            $query = $this->db->prepare($sql);
            $query->bindParam(":type",$type,PDO::PARAM_INT);
        } else {
            $sql = <<<EOT
SELECT reactionID
FROM {$this->dbPrefix}_reactions;
EOT;
            $query = $this->db->prepare($sql);
        }
        $query->execute();
        $reactions = array();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            array_push($reactions, $row['reactionID']);
        }
        if (isset($this->memCache)) {
            $this->memCache->set($key,$reactions,0,86400);
        }
        return $reactions;
    }

    /**
     * Return type ID of racial fuel block for a given race.
     * @param	int $race
     * @return	int
     */
    public function mapRaceIDToFuelBlockID($race) {
        switch ($race) {
            case "1":
                //Amarr
                return 4247;
            case "2":
                //Caldari
                return 4051;
            case "3":
                //Gallente
                return 4312;
            case "4":
                //Minmatar
                return 4246;
            default:
                return 0;
        }
    }

    /**
     * Return type ID of racial fuel block for a given race.
     * @param	int $race
     * @return	int
     */
    public function mapRaceIDToRaceName($race) {
        switch ($race) {
            case "1":
                return "Amarr";
            case "2":
                return "Caldari";
            case "3":
                return "Gallente";
            case "4":
                return "Minmatar";
            default:
                return "Unknown";
        }
    }

    public function getAllTypeIDs() {
        $sql = "SELECT typeID FROM {$this->dbPrefix}_types ORDER BY typeID ASC";
        $query = $this->db->prepare($sql);

        $typeIDs = array();
        $query->execute();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            array_push($typeIDs,$row['typeID']);
        }
        return $typeIDs;
    }
}