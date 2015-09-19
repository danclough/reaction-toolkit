<?php
class Reaction {
	private $db, $dbPrefix, $memCache;
    private $reactionID, $reactionType, $reactionName, $inputs, $output, $outputQty;
    private $inputVolume, $outputVolume;
	
	public function __construct($reactionID) {
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

        if(USE_MEMCACHED) {
            $this->memCache = new Memcache();
            $this->memCache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
        }

        $this->reactionID = $reactionID;

        $functionCall = __METHOD__."({$reactionID})";

        //Query DB for reaction inputs and quantities
        $key = hash("md5",$functionCall);
        if (USE_MEMCACHED) {
            // Check memcache for this particular call
            $cachedResult = $this->memCache->get($key);
            if ($cachedResult !== FALSE) {
                // Cache hit
            }
        }

        $sql = <<<EOT
SELECT *
FROM {$this->dbPrefix}_reactions
WHERE {$this->dbPrefix}_reactions.reactionID=:reactionID;
EOT;
        $query = $this->db->prepare($sql);
        $query->bindParam(":reactionID",$this->reactionID,PDO::PARAM_INT);

        if ($query->execute()) {
            if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $this->output = new Type($row['typeID']);
                $this->outputQty = $row['outputQty'];
                $this->reactionType = $row['reactionType'];
            } else {
                throw new Exception("Unable to locate a reaction with reactionID={$this->reactionID} in the database.");
            }
        } else {
            throw new Exception("An error occurred while trying to query the database.");
        }
    }

	/**
	 * Return an associative array of all inputs for this reaction.
	 * @return	array	(1D array if $chain = 0, 2D array if $chain = 1)
	 */
	public function getInputs() {
        if (!isset($this->inputs)) {
            $functionCall = __METHOD__."({$this->reactionID})";

            //Query DB for reaction inputs and quantities
            $key = hash("md5",$functionCall);
            if (USE_MEMCACHED) {
                // Check memcache for this particular call
                $cachedResult = $this->memCache->get($key);
                if ($cachedResult !== FALSE) {
                    // Cache hit
                    $this->inputs = $cachedResult;
                    return $this->inputs;
                }
            }

            $sql = <<<EOT
SELECT {$this->dbPrefix}_inputs.typeID, {$this->dbPrefix}_inputs.inputQty
FROM {$this->dbPrefix}_inputs
WHERE {$this->dbPrefix}_inputs.reactionID=:reactionID;
EOT;
            $query = $this->db->prepare($sql);
            $query->bindParam(":reactionID",$this->reactionID,PDO::PARAM_INT);

            $this->inputs = array();
            if ($query->execute()) {
                $this->inputs = $query->fetchAll(PDO::FETCH_ASSOC);
                if (USE_MEMCACHED && count($this->inputs) > 0) {
                    $this->memCache->set($key, $this->inputs, 0, 86400);
                }
            }
        }
		return $this->inputs;
	}

    public function getOutput() {
        if (isset($this->output)) {
            return $this->output;
        }
        return null;
    }

    public function getReactionName() {
        switch ($this->reactionType) {
            case 3:
                return $this->output->getName()." Alchemy";
                break;
            default:
                return $this->output->getName();
                break;
        }
    }

    public function getReactionType() {
        if (isset($this->reactionType)) {
            return $this->reactionType;
        }
        return null;
    }

	/**
	* Query database to determine total input volume of a reaction.
	* @return	int
	*/
	public function getInputVolume() {
        if (!isset($this->inputVolume)) {
            $totalVolume = 0;
            foreach ($this->inputs as $input) {
                $inputObject = new Type($input['typeID']);
                $inputVolume = $inputObject->getVolume() * $input['inputQty'];
                $totalVolume += $inputVolume;
            }
            $this->inputVolume = $totalVolume;
        }
        return $this->inputVolume;
	}

	/**
	* Query database to determine total output volume of a reaction.
	* @return float
	*/
	public function getOutputVolume() {
        if (!isset($this->outputVolume)) {
            $this->outputVolume = $this->outputQty * $this->output->getVolume();
        }
        return $this->outputVolume;
	}

    public function getOutputQty() {
        if (isset($this->outputQty)) {
            return $this->outputQty;
        }
        return null;
    }
}
