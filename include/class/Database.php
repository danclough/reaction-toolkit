<?php

/**
 * Database handler class
 * Looks things up in the database/cache so other classes don't have to.
 */
class Database {
    private $db, $dbPrefix, $cache;

    public function __construct()
    {
        $this->dbPrefix = "";
        if (defined(DATABASE_PREFIX) && !empty(DATABASE_PREFIX)) {
            $this->dbPrefix = DATABASE_PREFIX . "_";
        }
        try {
            // Create database object
            $this->db = new PDO("mysql:host=" . DATABASE_HOST . ";dbname=" . DATABASE_NAME . ";charset=utf8", DATABASE_USERNAME, DATABASE_PASSWORD);
            // PDO Security and Error Handling settings
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Exception $e) {
            echo "Failed to connect to database.  " . $e->getMessage() . " (Code " . $e->getCode() . ")";
            die();
        }
        $this->cache = new Cache();
    }

    public function getReactionIDFromTypeID($typeID, $isAlchemy) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cache->isCached($key)) {
            return $this->cache->load($key);
        } else {
            if ($isAlchemy) {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions WHERE typeID=:typeID AND reactionType=3;";
            } else {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions WHERE typeID=:typeID AND reactionType <> 3;";
            }
            $query = $this->db->prepare($sql);
            $query->bindParam(":typeID", $typeID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->cache->save($key, $row['reactionID'], 86400);
                return $row['reactionID'];
            }
        }
        return null;
    }

    public function getItemName($typeID) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cache->isCached($key)) {
            return $this->cache->load($key);
        } else {
            $sql = "SELECT itemName FROM {$this->dbPrefix}types WHERE typeID=:typeID;";
            $query = $this->db->prepare($sql);
            $query->bindParam(":typeID", $typeID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->cache->save($key,$row['itemName'],86400);
                return $row['itemName'];
            }
        }
        return null;
    }

    public function getItemData($typeID) {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cache->isCached($key)) {
            return $this->cache->load($key);
        } else {
            // Cache miss or no cache at all
            $sql = "SELECT * FROM {$this->dbPrefix}types WHERE typeID=:typeID;";
            $query = $this->db->prepare($sql);
            $query->bindParam(":typeID", $typeID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                if (count($row) > 0) {
                    $this->cache->save($key, $row, 86400);
                    return $row;
                }
            }
        }
        return null;
    }

    /**
     * Returns all columns of all rows from the items table as an associative array keyed by typeID.
     * @param	none
     * @return	array
     */
    function getAllTypes() {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cache->isCached($key)) {
            $types = $this->cache->load($key);
        } else {
            $sql = "SELECT * FROM {$this->dbPrefix}types ORDER BY typeID ASC;";
            $query = $this->db->prepare($sql);
            $query->execute();
            $types = array();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $typeID = $row['typeID'];
                $types[$typeID] = $row;
            }
        }
        return $types;
    }

    public function getAllTypeIDs() {
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ")";
        if ($this->cache->isCached($key)) {
            $typeIDs = $this->cache->load($key);
        } else {
            $sql = "SELECT typeID FROM {$this->dbPrefix}types ORDER BY typeID ASC";
            $query = $this->db->prepare($sql);
            $query->execute();
            if ($typeIDs = $query->fetchAll(PDO::FETCH_COLUMN,0)) {
                if (count($typeIDs) > 0) {
                    $this->cache->save($key, $typeIDs, 86400);
                }
            }
        }
        return $typeIDs;
    }

    public function getPrice($typeID, $systemID, $datetime, $priceType, $loopCount = 0) {
        $key = __METHOD__."(".implode(",",func_get_args()).")";
        if ($this->cache->isCached($key)) {
            return $this->cache->load($key);
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
                    WHERE typeID=:typeID AND systemID=:systemID AND datetime=:datetime
                    ORDER BY datetime DESC LIMIT 0, 1;";
            $query = $this->db->prepare($sql);
            $query->bindParam(":typeID", $typeID, PDO::PARAM_INT);
            $query->bindParam(":systemID", $systemID, PDO::PARAM_INT);
            $query->bindParam(":datetime", $datetime, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $price = $row[$priceStr];
                $this->cache->save($key, $price, 300);
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
            return $price;
        }
    }

    public function getSystemName($systemID) {
        $key = __METHOD__."(".implode(",",func_get_args()).")";
        if ($this->cache->isCached($key)) {
            return $this->cache->load($key);
        } else {
            $sql = "SELECT systemName FROM {$this->dbPrefix}systems WHERE systemID=:systemID;";
            $query = $this->db->prepare($sql);
            $query->bindParam(":systemID", $systemID, PDO::PARAM_INT);
            $query->execute();
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->cache->save($key,$row['systemName'],86400);
                return $row['systemName'];
            }
        }
        return null;
    }

    public function getSystemPrices($systemID) {
        $datetime = $this->getLastTimestamp(time());
        $key = __METHOD__ . "(" . implode(",", func_get_args()) . ",$datetime)";
        if ($this->cache->isCached($key)) {
            return $this->cache->load($key);
        } else {
            $sql = "SELECT {$this->dbPrefix}types.typeID, itemName, minSell, maxBuy
                    FROM {$this->dbPrefix}types
                    JOIN {$this->dbPrefix}prices ON {$this->dbPrefix}types.typeID = {$this->dbPrefix}prices.typeID
                    WHERE systemID=:systemID AND datetime=:datetime ORDER BY itemName ASC;";
            $query = $this->db->prepare($sql);
            $query->bindParam(":systemID", $systemID, PDO::PARAM_INT);
            $query->bindParam(":datetime", $datetime, PDO::PARAM_STR);
            $query->execute();
            if ($rows = $query->fetchAll(PDO::FETCH_ASSOC)) {
                $this->cache->save($key,$rows,300);
                return $rows;
            }
        }
    }

    public function getAllReactions($type = NULL)
    {
        $key = __METHOD__."(".implode(",",func_get_args()).")";
        if ($this->cache->isCached($key)) {
            $reactions = $this->cache->load($key);
        } else {
            if (isset($type)) {
                $sql = "SELECT {$this->dbPrefix}reactions.reactionID, {$this->dbPrefix}types.itemName
                        FROM {$this->dbPrefix}types
                        JOIN {$this->dbPrefix}reactions on {$this->dbPrefix}reactions.typeID = {$this->dbPrefix}types.typeID
                        WHERE reactionType = :type ORDER BY itemName ASC;";
                $query = $this->db->prepare($sql);
                $query->bindParam("type",$type,PDO::PARAM_INT);
            } else {
                $sql = "SELECT {$this->dbPrefix}reactions.reactionID, {$this->dbPrefix}reactions.reactionType, {$this->dbPrefix}types.itemName
                        FROM {$this->dbPrefix}types
                        JOIN {$this->dbPrefix}reactions on {$this->dbPrefix}reactions.typeID = {$this->dbPrefix}types.typeID
                        ORDER BY reactionType, itemName ASC;";
                $query = $this->db->prepare($sql);
            }
            $query->execute();
            if ($reactions = $query->fetchAll(PDO::FETCH_ASSOC)) {
                if (count($reactions) > 0) {
                    $this->cache->save($key, $reactions, 86400);
                }
            }
        }
        return $reactions;
    }

    public function getAllReactionIDs($type = NULL)
    {
        $key = __METHOD__."(".implode(",",func_get_args()).")";
        if ($this->cache->isCached($key)) {
            $reactionIDs = $this->cache->load($key);
        } else {
            if (isset($type)) {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions WHERE reactionType=:type;";
                $query = $this->db->prepare($sql);
                $query->bindParam(":type", $type, PDO::PARAM_INT);
            } else {
                $sql = "SELECT reactionID FROM {$this->dbPrefix}reactions;";
                $query = $this->db->prepare($sql);
            }
            $query->execute();
            if ($reactionIDs = $query->fetchAll(PDO::FETCH_COLUMN,0)) {
                if (count($reactionIDs) > 0) {
                    $this->cache->save($key, $reactionIDs, 86400);
                }
            }
        }
        return $reactionIDs;
    }

    /**
     * Returns all columns of all rows from the systems table as an associative array keyed by systemID.
     * @param	none
     * @return	array
     */
    public function getAllSystems() {
        $key = __METHOD__."(".implode(",",func_get_args()).")";
        if ($this->cache->isCached($key)) {
            $systems = $this->cache->load($key);
        } else {
            $sql = "SELECT systemID, systemName FROM {$this->dbPrefix}systems ORDER BY systemID ASC;";
            $query = $this->db->prepare($sql);
            $query->execute();
            if ($systems = $query->fetchAll(PDO::FETCH_KEY_PAIR)) {
                if (count($systems) > 0) {
                    $this->cache->save($key, $systems, 86400);
                }
            }
        }
        return $systems;
    }

    /**
     * Returns all systemIDs from the systems table as an iterative array.
     * @param	none
     * @return	array
     */
    public function getAllSystemIDs() {
        $systems = $this->getAllSystems();
        return array_keys($systems);
    }

    /**
     * Takes MySQL update records as an array of VALUES strings and inserts them into the database, updating b and s for duplicate keys.
     * @param	array	$tuples		An array of strings in the format of ('systemID','typeID','datetime','b','s').
     * @return	bool	A boolean indicating database update success or failure.
     */
    public function storePriceRecords($tuples) {
        //Start transaction so we can roll back if anything fails.
        $this->db->beginTransaction();
        // Implode tuples array to comma-separated string of tuples
        $tupleStr = implode(",",$tuples);
        $sql = "INSERT INTO {$this->dbPrefix}prices (systemID,typeID,datetime,maxBuy,minSell) VALUES {$tupleStr} ON DUPLICATE KEY UPDATE maxBuy=VALUES(maxBuy), minSell=VALUES(minSell);";

        $statement = $this->db->prepare($sql);
        //$statement->bindParam(":tuples",$tupleStr,PDO::PARAM_STR);

        if ($statement->execute()) {
                return $this->db->commit();
        } else {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Calculate average daily prices for all items across all defined market systems for the specified date, and save
     * average and median prices in the historicalprices table.
     * @param	str $datetime	A datetime in Y-m-d H:i:s format
     * @return	bool			A boolean indicating database update success or failure.
     */
    public function processDailyHistoricalPrices($datetime) {
        $date = date("Y-m-d",strtotime($datetime));

        //Retrieve system and type IDs as iterative arrays
        $systemIDs = $this->getAllSystemIDs();
        $systemID = null;
        $typeIDs = $this->getAllTypeIDs();
        $typeID = null;

        //Define new array for the update statements (tuples)
        $tuples = array();

        $sql = "SELECT maxBuy, minSell
                FROM {$this->dbPrefix}prices
                WHERE typeID=:typeID AND systemID=:systemID AND datetime LIKE '{$date}%';";
        $query = $this->db->prepare($sql);
        $query->bindParam(':typeID',$typeID,PDO::PARAM_INT);
        $query->bindParam(':systemID',$systemID,PDO::PARAM_INT);

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
                    array_push($maxBuys,$row['maxBuy']);
                    array_push($minSells,$row['minSell']);
                }
                //Calculate average of prices, format update record and push to updates array
                if (count($maxBuys) >= 2) {
                    $avgMaxBuy = round(array_sum($maxBuys) / count($maxBuys),2);
                    $medMaxBuy = $this->calculate_median($maxBuys);
                } elseif (count($maxBuys) == 1) {
                    $avgMaxBuy = $maxBuys[0];
                    $medMaxBuy = $maxBuys[0];
                } else {
                    $avgMaxBuy = 0;
                    $medMaxBuy = 0;
                }
                if (count($minSells) >= 2) {
                    $avgMinSell = round(array_sum($minSells) / count($minSells),2);
                    $medMinSell = $this->calculate_median($minSells);
                } elseif (count($minSells) == 1) {
                    $avgMinSell = $minSells[0];
                    $medMinSell = $minSells[0];
                } else {
                    $avgMinSell = 0;
                    $medMinSell = 0;
                }
                $tuple = "({$systemID},{$typeID},'{$date} 00:00:00',{$avgMaxBuy},{$medMaxBuy},{$avgMinSell},{$medMinSell})";
                array_push($tuples,$tuple);
            }
        }

        //Implode updates array to create comma-separated string to feed into SQL query
        $tupleStr = implode(",",$tuples);

        $sql = "INSERT INTO {$this->dbPrefix}historicalprices (systemID, typeID, datetime, avgMaxBuy, medMaxBuy, avgMinSell, medMinSell)
                VALUES {$tupleStr}
                ON DUPLICATE KEY UPDATE avgMaxBuy=VALUES(avgMaxBuy), medMaxBuy=VALUES(medMaxBuy), avgMinSell=VALUES(avgMinSell), medMinSell=VALUES(medMinSell);";
        $statement = $this->db->prepare($sql);

        //Run update query, return true/false indicating success or fail
        return $statement->execute();
    }

    /**
     * Calculate median of all values in an array.
     * @param	array	$array	A 1D array of numbers
     * @return	number		Median of all array values
     */
    private function calculate_median($array) {
        sort($array);
        $count = count($array); //total numbers in array
        if($count > 1) {
            $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
            if($count % 2) { // odd number, middle is the median
                $median = $array[$middleval];
            } else { // even number, calculate avg of 2 medians
                $low = $array[$middleval];
                $high = $array[$middleval+1];
                $median = (($low+$high)/2);
            }
        }
        elseif ($count >0) {
            return $array[0];
        }
        else {
            $median = 0;
        }
        return $median;
    }

    /**
     * Remove price data from a specified date from the database.
     * @param	str $datetime	A datetime in Y-m-d H:i:s format
     * @return	bool		Indicates database update success/failure
     */
    public function deletePriceData($datetime) {
        $date = date("Y-m-d",strtotime($datetime));
        $sql = "DELETE from {$this->dbPrefix}prices WHERE datetime LIKE '{$date}%';";
        $statement = $this->db->prepare($sql);

        //Run update query, return true/false indicating success or fail
        return $statement->execute();
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
        $time -= $time%$modulo;
        return date('Y-m-d H:i:s',$time);
    }
}