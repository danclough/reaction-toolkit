<?php

/**
 * Provides database functions to other classes so they don't have to hold
 * database connections and manage schema themselves.
 */
class DatabaseManager {

    /**
     * @var PDO $pdo A handle to connect to and query the configured database.
     */
    private $pdo = null;

    /**
     * @var string $dbPrefix A string to prepend to all table names.
     */
    private $dbPrefix = null;

    /**
     * @var CacheManager $cacheMgr Provides functions to cache query results.
     */
    private $cacheMgr = null;

    public function __construct($persistent = true) {
        $this->cacheMgr = new CacheManager();
        $this->dbPrefix = "";
        if (defined("DATABASE_PREFIX") && DATABASE_PREFIX != "") {
            $this->dbPrefix = DATABASE_PREFIX . "_";
        }
        try {
            // Create database object
            if ($persistent === true) {
                $options = array(PDO::ATTR_PERSISTENT => true);
            } else {
                $options = array();
            }
            $this->pdo = new PDO("mysql:host=" . DATABASE_HOST . ";dbname=" . DATABASE_NAME . ";charset=utf8", DATABASE_USERNAME, DATABASE_PASSWORD, $options);
            // PDO Security and Error Handling settings
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            echo "Failed to connect to database.  " . $e->getMessage() . " (Code " . $e->getCode() . ")";
            die();
        }
    }

    public function __destruct() {
        $this->dbPrefix = null;
        //Nullify cache and PDO connections to explicitly order them to close.
        $this->cacheMgr = null;
        $this->pdo = null;
    }

    /**
     * Returns all known data on a specified system.
     * 
     * @param int $systemID A valid system ID from the systems table.
     * @return array An associative array of data from the systems table.
     */
    public function getSystemData($systemID) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $row = $this->cacheMgr->load($key);
        } else {
            $row = null;
            // Cache miss or no cache at all
            $sql = "SELECT * FROM {$this->dbPrefix}systems WHERE systemID=:s;";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":s", $systemID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                if (count($row) > 0) {
                    $this->cacheMgr->save($key, $row, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $row;
    }

    /**
     * Returns all known data on a specified item type.
     * 
     * @param int $typeID A valid type ID from the types table.
     * @return array An associative array of data from the types table.
     */
    public function getTypeData($typeID) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $row = $this->cacheMgr->load($key);
        } else {
            $row = null;
            // Cache miss or no cache at all
            $sql = "SELECT * FROM {$this->dbPrefix}types WHERE typeID=:t;";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":t", $typeID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                if (count($row) > 0) {
                    $this->cacheMgr->save($key, $row, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $row;
    }

    /**
     * Returns all known data on a specified reaction.
     * 
     * @param int $reactionID A valid reaction ID from the reactions table.
     * @return array An associative array of data from the reactions table.
     */
    public function getReactionData($reactionID) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $row = $this->cacheMgr->load($key);
        } else {
            // Cache miss or no cache at all
            $sql = "SELECT * FROM {$this->dbPrefix}reactions WHERE reactionID=:r;";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":r", $reactionID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                if (count($row) > 0) {
                    $this->cacheMgr->save($key, $row, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $row;
    }

    /**
     * Returns a two-dimensional array of all inputs for this reaction.
     * 
     * The output for this function is an iterative array of two or more
     * associative arrays.  The associative arrays contain the type ID (typeID)
     * and required input quantity (inputQty) for each input.
     * 
     * @param int $reactionID A valid reaction ID from the reactions table.
     * @return array An iterative array of associative arrays for each reaction input.
     */
    public function getReactionInputs($reactionID) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $rows = $this->cacheMgr->load($key);
        } else {
            $row = null;
            $sql = "SELECT typeID, inputQty FROM {$this->dbPrefix}inputs WHERE reactionID=:r;";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":r", $reactionID, PDO::PARAM_INT);
            if ($query->execute()) {
                $rows = $query->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) > 0) {
                    $this->cacheMgr->save($key, $rows, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $rows;
    }

    /**
     * Returns the ID of the reaction required to produce the specified item
     * type.
     * 
     * @param int $typeID A valid type ID from the types table.
     * @param bool $isAlchemy Indicates whether the lookup should return the corresponding alchemy reaction instead.
     * @return int The ID of the corresponding reaction.
     */
    public function getReactionID($typeID, $isAlchemy = false) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $reactionID = $this->cacheMgr->load($key);
        } else {
            if ($isAlchemy) {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions WHERE typeID=:t AND reactionType=3;";
            } else {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions WHERE typeID=:t AND reactionType<>3;";
            }
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":t", $typeID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->cacheMgr->save($key, $row['reactionID'], 86400);
                $reactionID = $row['reactionID'];
            }
            $query->closeCursor();
            $query = null;
        }
        return $reactionID;
    }

    /**
     * Returns all data on the types table as an associative array.
     * 
     * @return array A two-dimensional array of data keyed by typeID.
     */
    public function getAllTypes() {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $types = $this->cacheMgr->load($key);
        } else {
            $sql = "SELECT * FROM {$this->dbPrefix}types ORDER BY typeID ASC;";
            $query = $this->pdo->prepare($sql);
            $query->execute();
            $types = array();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $typeID = $row['typeID'];
                $types[$typeID] = $row;
            }
            $query->closeCursor();
            $query = null;
        }
        return $types;
    }

    public function getPrice($typeID, $systemID, $datetime, $priceType, $loopCount = 0) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $price = $this->cacheMgr->load($key);
        } else {
            $price = 0;
            $yesterday = $this->getLastTimestamp(time() - 86400, 86400);
            if (strtotime($yesterday) > strtotime($datetime)) {
                $historical = true;
                $table = "historicalprices";
                if ($priceType == "b") {
                    $priceStr = "medMaxBuy";
                } else {
                    $priceStr = "medMinSell";
                }
            } else {
                $historical = false;
                $table = "prices";
                if ($priceType == "b") {
                    $priceStr = "maxBuy";
                } else {
                    $priceStr = "minSell";
                }
            }
            $sql = "SELECT {$priceStr} FROM {$this->dbPrefix}{$table}
                    WHERE typeID=:t AND systemID=:s AND datetime=:d
                    ORDER BY datetime DESC LIMIT 0, 1;";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":t", $typeID, PDO::PARAM_INT);
            $query->bindParam(":s", $systemID, PDO::PARAM_INT);
            $query->bindParam(":d", $datetime, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $price = $row[$priceStr];
                $this->cacheMgr->save($key, $price, 300);
            } else {
                if (!$historical && $loopCount < 5) {
                    $datetime = $this->getLastTimestamp(strtotime($datetime) - 300, 300);
                    $price = $this->getPrice($typeID, $systemID, $datetime, $priceType, $loopCount + 1);
                } elseif ($loopCount < 5) {
                    $datetime = $this->getLastTimestamp(strtotime($datetime) - 86400, 86400);
                    $price = $this->getPrice($typeID, $systemID, $datetime, $priceType, 5);
                } elseif ($loopCount >= 5) {
                    $price = 0;
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $price;
    }

    public function getSystemPrices($systemID) {
        $datetime = $this->getLastTimestamp(time());
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ",$datetime)";
        if ($this->cacheMgr->isCached($key)) {
            $rows = $this->cacheMgr->load($key);
        } else {
            $sql = "SELECT {$this->dbPrefix}types.typeID, itemName, minSell, maxBuy
                    FROM {$this->dbPrefix}types
                    JOIN {$this->dbPrefix}prices ON {$this->dbPrefix}types.typeID = {$this->dbPrefix}prices.typeID
                    WHERE systemID=:s AND datetime=:d ORDER BY itemName ASC;";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(":s", $systemID, PDO::PARAM_INT);
            $query->bindParam(":d", $datetime, PDO::PARAM_STR);
            $query->execute();
            if ($rows = $query->fetchAll(PDO::FETCH_ASSOC)) {
                $this->cacheMgr->save($key, $rows, 300);
            }
            $query->closeCursor();
            $query = null;
        }
        return $rows;
    }

    public function getAllReactions($type = NULL) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $reactions = $this->cacheMgr->load($key);
        } else {
            if (isset($type)) {
                $sql = "SELECT {$this->dbPrefix}reactions.reactionID, {$this->dbPrefix}types.itemName
                        FROM {$this->dbPrefix}types
                        JOIN {$this->dbPrefix}reactions on {$this->dbPrefix}reactions.typeID = {$this->dbPrefix}types.typeID
                        WHERE reactionType = :t ORDER BY itemName ASC;";
                $query = $this->pdo->prepare($sql);
                $query->bindParam(":t", $type, PDO::PARAM_INT);
            } else {
                $sql = "SELECT {$this->dbPrefix}reactions.reactionID, {$this->dbPrefix}reactions.reactionType, {$this->dbPrefix}types.itemName
                        FROM {$this->dbPrefix}types
                        JOIN {$this->dbPrefix}reactions on {$this->dbPrefix}reactions.typeID = {$this->dbPrefix}types.typeID
                        ORDER BY reactionType, itemName ASC;";
                $query = $this->pdo->prepare($sql);
            }
            $query->execute();
            if ($reactions = $query->fetchAll(PDO::FETCH_ASSOC)) {
                if (count($reactions) > 0) {
                    $this->cacheMgr->save($key, $reactions, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $reactions;
    }

    public function getAllReactionIDs($type = NULL) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $reactionIDs = $this->cacheMgr->load($key);
        } else {
            if (isset($type)) {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions WHERE reactionType=:t;";
                $query = $this->pdo->prepare($sql);
                $query->bindParam(":t", $type, PDO::PARAM_INT);
            } else {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions;";
                $query = $this->pdo->prepare($sql);
            }
            $query->execute();
            if ($reactionIDs = $query->fetchAll(PDO::FETCH_COLUMN, 0)) {
                if (count($reactionIDs) > 0) {
                    $this->cacheMgr->save($key, $reactionIDs, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $reactionIDs;
    }

    /**
     * Returns all columns of all rows from the systems table as an associative array keyed by systemID.
     * @param	none
     * @return	array
     */
    public function getAllSystems() {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cacheMgr->isCached($key)) {
            $systems = $this->cacheMgr->load($key);
        } else {
            $sql = "SELECT systemID, systemName FROM {$this->dbPrefix}systems ORDER BY systemID ASC;";
            $query = $this->pdo->prepare($sql);
            $query->execute();
            if ($systems = $query->fetchAll(PDO::FETCH_KEY_PAIR)) {
                if (count($systems) > 0) {
                    $this->cacheMgr->save($key, $systems, 86400);
                }
            }
            $query->closeCursor();
            $query = null;
        }
        return $systems;
    }

    /**
     * Takes MySQL update records as an array of VALUES strings and inserts them into the database, updating b and s for duplicate keys.
     * @param	array	$tuples		An array of strings in the format of ('systemID','typeID','datetime','b','s').
     * @return	bool	A boolean indicating database update success or failure.
     */
    public function storePriceRecords($tuples) {
        // Implode tuples array to comma-separated string of tuples
        $tupleStr = implode(",", $tuples);
        $sql = "INSERT INTO {$this->dbPrefix}prices (systemID,typeID,datetime,maxBuy,minSell)
                VALUES {$tupleStr}
                ON DUPLICATE KEY UPDATE maxBuy=VALUES(maxBuy), minSell=VALUES(minSell);";

        $statement = $this->pdo->prepare($sql);
        //$statement->bindParam(":tuples",$tupleStr,PDO::PARAM_STR);

        $result = $statement->execute();
        $statement->closeCursor();
        $statement = null;
        return $result;
    }

    /**
     * Calculate average daily prices for all items across all defined market systems for the specified date, and save
     * average and median prices in the historicalprices table.
     * @param	str $datetime	A datetime in Y-m-d H:i:s format
     * @return	bool			A boolean indicating database update success or failure.
     */
    public function processDailyHistoricalPrices($datetime) {
        $date = date("Y-m-d", strtotime($datetime));

        //Retrieve system and type IDs as iterative arrays
        $systemIDs = array_keys($this->getAllSystems());
        $systemID = null;
        $typeIDs = array_keys($this->getAllTypes());
        $typeID = null;

        //Define new array for the update statements (tuples)
        $tuples = array();

        $sql = "SELECT maxBuy, minSell
                FROM {$this->dbPrefix}prices
                WHERE typeID=:t AND systemID=:s AND datetime LIKE '{$date}%';";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(":t", $typeID, PDO::PARAM_INT);
        $query->bindParam(":s", $systemID, PDO::PARAM_INT);

        foreach ($systemIDs as $systemID) {
            foreach ($typeIDs as $typeID) {
                // Execute DB query with current typeID/systemID
                $query->execute();

                // Initialize array for price data
                $maxBuys = array();
                $minSells = array();

                // Populate price arrays with query results
                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    //Push respective prices to their own arrays
                    array_push($maxBuys, $row['maxBuy']);
                    array_push($minSells, $row['minSell']);
                }
                //Calculate average of prices, format update record and push to updates array
                if (count($maxBuys) >= 2) {
                    $avgMaxBuy = round(array_sum($maxBuys) / count($maxBuys), 2);
                    $medMaxBuy = calculate_median($maxBuys);
                } elseif (count($maxBuys) == 1) {
                    $avgMaxBuy = $maxBuys[0];
                    $medMaxBuy = $maxBuys[0];
                } else {
                    $avgMaxBuy = 0;
                    $medMaxBuy = 0;
                }
                if (count($minSells) >= 2) {
                    $avgMinSell = round(array_sum($minSells) / count($minSells), 2);
                    $medMinSell = calculate_median($minSells);
                } elseif (count($minSells) == 1) {
                    $avgMinSell = $minSells[0];
                    $medMinSell = $minSells[0];
                } else {
                    $avgMinSell = 0;
                    $medMinSell = 0;
                }
                $tuple = "({$systemID},{$typeID},'{$date} 00:00:00',{$avgMaxBuy},{$medMaxBuy},{$avgMinSell},{$medMinSell})";
                array_push($tuples, $tuple);
            }
        }

        //Implode updates array to create comma-separated string to feed into SQL query
        $tupleStr = implode(",", $tuples);

        $sql = "INSERT INTO {$this->dbPrefix}historicalprices 
                    (systemID, typeID, datetime, avgMaxBuy, medMaxBuy, 
                    avgMinSell, medMinSell)
                VALUES {$tupleStr}
                ON DUPLICATE KEY UPDATE 
                    avgMaxBuy=VALUES(avgMaxBuy), 
                    medMaxBuy=VALUES(medMaxBuy), 
                    avgMinSell=VALUES(avgMinSell), 
                    medMinSell=VALUES(medMinSell);";
        $statement = $this->pdo->prepare($sql);

        $result = $statement->execute();
        $statement->closeCursor();
        $statement = null;
        return $result;
    }

    /**
     * Remove price data from a specified date from the database.
     * @param	str $datetime	A datetime in Y-m-d H:i:s format
     * @return	bool		Indicates database update success/failure
     */
    public function deletePriceData($datetime) {
        $date = date("Y-m-d", strtotime($datetime));
        $sql = "DELETE from {$this->dbPrefix}prices
                WHERE datetime LIKE '{$date}%';";
        $statement = $this->pdo->prepare($sql);

        //Run update query, return true/false indicating success or fail
        $result = $statement->execute();
        $statement->closeCursor();
        $statement = null;
        return $result;
    }

    /**
     * Return type ID of racial fuel block for a given race.
     * @param	int $race
     * @return	int
     */
    public function getFuelBlockID($race) {
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
    public function getRaceName($race) {
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

    /**
     * Return number of hourly cycles in a given timeframe.
     * @param	string $timeframe
     * @return	int
     */
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

    /**
     * Round a given timestamp down to the nearest specified interval.
     *
     * Used to normalize timestamps to a standard interval for price lookups.
     *
     * @param $time Unix timestamp
     * @param int $modulo Number of seconds to round down to
     * @return string String of the resulting date/time.
     */
    public function getLastTimestamp($time, $modulo = 300) {
        $time -= $time % $modulo;
        return date('Y-m-d H:i:s', $time);
    }

}
