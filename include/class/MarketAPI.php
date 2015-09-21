<?php

class MarketAPI {

    /**
     * An associative array containing details for all valid API Targets.
     */
    private $apiTargets = array();

    /**
     * Associative array containing EVE-Central XML API details.
     */
    private $evecXML = array(
        "url" => "http://api.eve-central.com/api/marketstat",
        "function" => "updatePricesFromEVECentralXML",
        "description" => "<a href='http://www.eve-central.com'>EVE-Central.com</a> XML feed"
    );

    /**
     * Associative array containing EVE-Central JSON API details.
     */
    private $evecJSON = array(
        "url" => "http://api.eve-central.com/api/marketstat/json",
        "function" => "updatePricesFromEVECentralJSON",
        "description" => "<a href='http://www.eve-central.com'>EVE-Central.com</a> JSON feed"
    );

    /**
     * Associative array containing EVE-MarketData JSON API details.
     */
    private $evemdJSON = array(
        "url" => "http://api.eve-marketdata.com/api/item_prices2.json",
        "function" => "updatePricesFromEVEMarketDataJSON",
        "description" => "<a href='http://www.eve-marketdata.com'>EVE-MarketData.com</a> JSON feed",
        "charName" => "Reaction-Toolkit"
    );

    /**
     * Default endpoint setting.
     *
     * This variable controls the default endpoint used by the MarketAPI class when no
     * other API endpoint has been specified at runtime.
     */
    private $primaryTarget = "evec-json";
    private $dbMgr;

    public function __construct() {
        // Populate the endpoint array
        $this->apiTargets = array(
            'evec-xml' => $this->evecXML,
            'evec-json' => $this->evecJSON,
            'evemd-json' => $this->evemdJSON
        );
        $this->dbMgr = new DatabaseManager(true);
    }

    /**
     * Change the current market API endpoint.
     *
     * This function changes the current endpoint to a user-specified value if it exists.
     *
     * @param	string	$target	A valid endpoint that exists in the apiTargets array.
     *
     * @return	bool	True/false indicating whether the change was successful.
     */
    public function setPrimaryTarget($target) {
        // 
        if (array_key_exists($target, $this->apiTargets)) {
            $this->primaryTarget = $target;
            return true;
        }
        return false;
    }

    /**
     * Update prices from the current API endpoint.
     *
     * This function references the current API endpoint to call the appropriate
     * function for a given endpoint.
     *
     * @return	mixed	If the update was successful, a string giving the description of
     * the endpoint that was used.  If unsuccessful, FALSE.
     */
    public function updatePrices() {
        $status = $this->{$this->apiTargets[$this->primaryTarget]['function']}();
        if ($status) {
            return $this->apiTargets[$this->primaryTarget]['description'];
        } else {
            foreach ($this->apiTargets as $apiTarget => $apiDetails) {
                $status = $this->{$apiDetails['function']}();
                if ($status) {
                    return $apiDetails['description'];
                }
            }
        }
        return false;
    }

    /**
     * Query EVE-Central XML API endpoint.
     *
     * Query EVE-Central buy/sell prices for all items across all defined market systems
     * and save latest data in database.
     *
     * @return	bool	A boolean indicating price update success or failure.
     */
    private function updatePricesFromEVECentralXML() {
        // Set config array for easier reference.
        $config = $this->evecXML;

        // Get the timestamp of the last 5-minute increment.
        $datetime = $this->dbMgr->getLastTimestamp(time(), 300);

        // Query DB for all systemIDs and typeIDs.
        $systemIDs = array_keys($this->dbMgr->getAllSystems());
        $typeIDs = array_keys($this->dbMgr->getAllTypes());

        // Split array of type IDs into separate sets of 50.
        // EVE-Central only supports up to 50 type IDs in a single query.
        $typeIDSets = array_chunk($typeIDs, 50);

        // Our temporary array to save MySQL value statements.
        // This will be passed to another function for storage in the database.
        $priceData = array();

        // Iterate through systems to query market data for all items.
        foreach ($systemIDs as $systemID) {
            foreach ($typeIDSets as $itemList) {
                try {
                    // Build XML URL for specified system and current subset of items.
                    $url = $config['url'];
                    $params = "?usesystem={$systemID}&typeid=" . join("&typeid=", $itemList);
                    $pricexml = @file_get_contents($url . $params);
                    if ($pricexml === false) {
                        return false;
                    }
                } catch (Exception $e) {
                    return false;
                }
                $xml = new SimpleXMLElement($pricexml);
                foreach ($itemList as $typeID) {
                    // Retrieve individual item data via xpath query.
                    $item = $xml->xpath("/evec_api/marketstat/type[@id={$typeID}]");

                    // Retrieve buy/sell prices from XML and create value statement.
                    $minSell = (float) $item[0]->sell->min;
                    $minSell = round($minSell, 2);
                    $maxBuy = (float) $item[0]->buy->max;
                    $maxBuy = round($maxBuy, 2);
                    array_push($priceData, "('{$systemID}','{$typeID}','{$datetime}','{$maxBuy}','{$minSell}')");
                }
            }
        }
        return $this->dbMgr->storePriceRecords($priceData);
    }

    /**
     * Query EVE-Central JSON EPI endpoint.
     *
     * Query EVE-Central buy/sell prices for all items across all defined market systems
     * and save latest data in database.
     *
     * @return	bool	A boolean indicating price update success or failure.
     */
    private function updatePricesFromEVECentralJSON() {
        // Set config array for easier reference.
        $config = $this->evecJSON;

        // Get the timestamp of the last 5-minute increment.
        $datetime = $this->dbMgr->getLastTimestamp(time(), 300);

        // Query DB for all systemIDs and typeIDs.
        $systemIDs = array_keys($this->dbMgr->getAllSystems());
        $typeIDs = array_keys($this->dbMgr->getAllTypes());

        // Split array of type IDs into separate sets of 50.
        // EVE-Central only supports up to 50 type IDs in a single query.
        $typeIDSets = array_chunk($typeIDs, 50);

        $priceData = array();

        // Iterate through systems to query market data for all items.
        foreach ($systemIDs as $systemID) {
            foreach ($typeIDSets as $itemList) {
                $url = $config['url'];
                $params = "?usesystem={$systemID}&typeid=" . join("&typeid=", $itemList);
                try {
                    $jsondata = @file_get_contents($url . $params);
                    if ($jsondata === false) {
                        return false;
                    }
                } catch (Exception $e) {
                    return false;
                }
                $results = json_decode($jsondata, true);
                foreach ($results as $dataset) {
                    // Retrieve individual item data from array.
                    $typeID = $dataset['all']['forQuery']['types'][0];

                    // Retrieve buy/sell prices from array and create value statement.
                    $systemID = $dataset['all']['forQuery']['systems'][0];
                    $maxBuy = $dataset['buy']['max'];
                    $minSell = $dataset['sell']['min'];
                    array_push($priceData, "('{$systemID}','{$typeID}','{$datetime}','{$maxBuy}','{$minSell}')");
                }
            }
        }
        return $this->dbMgr->storePriceRecords($priceData);
    }

    /**
     * Queries EVE MarketData JSON API for max buy/min sell prices for all items across all defined market systems and stores the data in the prices table.
     * @return	bool	A boolean indicating price update success or failure.
     */
    private function updatePricesFromEVEMarketDataJSON() {
        // Set config array for easier reference.
        $config = $this->evemdJSON;

        // Get the timestamp of the last 5-minute increment.
        $datetime = $this->dbMgr->getLastTimestamp(time(), 300);

        // Query DB for all systemIDs and typeIDs.
        $systemIDs = array_keys($this->dbMgr->getAllSystems());
        $typeIDs = array_keys($this->dbMgr->getAllTypes());

        // Build API URL from different options
        $url = $config['url'];
        $charName = $config['charName'];
        $charStr = "char_name=" . urlencode($charName);
        $typeIDStr = "type_ids=" . implode(",", $typeIDs);
        $solarSystemIDs = "solarsystem_ids=" . implode(",", $systemIDs);
        $params = "?{$charStr}&{$typeIDStr}&{$solarSystemIDs}&buysell=a";

        // Retrieve JSON data from EMD
        try {
            $jsondata = @file_get_contents($url . $params);
            if ($jsondata === false) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        // Decode JSON data into array for processing
        $pricedata = json_decode($jsondata, true);

        // Everything we care about is two levels deep, the rest is just header data
        $results = $pricedata['emd']['result'];

        // Parse decoded JSON array and merge separate buy/sell records into a single data structure for easier processing
        $combinedData = array();
        foreach ($results as $result) {
            $row = $result['row'];
            if ($row['buysell'] == 'b'):
                $priceType = "maxBuy";
            else:
                $priceType = "minSell";
            endif;
            $typeID = $row['typeID'];
            $systemID = $row['solarsystemID'];
            $price = round($row['price'], 2);
            $combinedData[$typeID][$systemID][$priceType] = $price;
        }

        // Process unified data into MySQL value update statements
        $priceData = array();
        foreach ($combinedData as $typeID => $itemData) {
            foreach ($itemData as $systemID => $systemPriceData) {
                $maxBuy = $systemPriceData['maxBuy'];
                $minSell = $systemPriceData['minSell'];
                array_push($priceData, "('{$systemID}','{$typeID}','{$datetime}','{$maxBuy}','{$minSell}')");
            }
        }
        return $this->dbMgr->storePriceRecords($priceData);
    }

}
