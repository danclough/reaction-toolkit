<?php
class Calculator {
    private $reaction, $dbMgr, $numCycles, $chain, $systemID, $datetime, $iPrice, $fPrice, $oPrice, $timeframe;
    private $hourlyRevenue, $hourlyFuelVolume, $hourlyFuelCost, $hourlyInputCost, $numTowers, $hourlyNetIncome;
    private $inputs, $inputVolume;
    private $output, $outputVolume;
    private $cacheMgr;
    private $factory;
    private $instanceID;


    public function __construct($reactionID,$chain,$datetime)
    {
        $this->factory = new ObjectFactory();
        $this->cacheMgr = new CacheManager();
        $this->dbMgr = new DatabaseManager(true);
        $this->race = $_SESSION['params']['r'];
        $this->sov = $_SESSION['params']['s'];
        $this->gsf = $_SESSION['params']['g'];
        $this->systemID = $_SESSION['params']['sy'];
        $this->datetime = $datetime;
        $this->iPrice = $_SESSION['params']['i'];
        $this->fPrice = $_SESSION['params']['f'];
        $this->oPrice = $_SESSION['params']['o'];
        $this->timeframe = $_SESSION['params']['t'];
        $this->numCycles = 0;
        $this->inputs = array();
        $this->numCycles = $this->dbMgr->getNumCycles($this->timeframe);
        $this->reaction = $this->factory->create(ObjectFactory::REACTION, $reactionID);
        if ($this->reaction->getReactionType() == 2 && $chain) {
            $this->chain = TRUE;
        } else {
            $this->chain = FALSE;
        }
        if ($this->chain) {
            $intermediateInputs = $this->reaction->getInputs();
            foreach ($intermediateInputs as $intermediateInput) {
                $intermediateTypeID = $intermediateInput['typeID'];
                $intermediateReactionID = $this->dbMgr->getReactionID($intermediateTypeID,FALSE);
                $intermediateReaction = $this->factory->create(ObjectFactory::REACTION, $intermediateReactionID);
                array_push($this->inputs, $intermediateReaction->getInputs());
            }
        } else {
            $this->inputs = $this->reaction->getInputs();
        }
        $this->output = $this->reaction->getOutput();
        $this->instanceID = __CLASS__.":".$reactionID.":".$chain.":".$datetime.":".implode(":",$_SESSION['params']);
    }

    public function getInputs() {
        if (isset($this->inputs)) {
            return $this->inputs;
        }
        return null;
    }

    public function getReaction() {
        if (isset($this->reaction)) {
            return $this->reaction;
        }
        return null;
    }

    public function getOutput() {
        if (isset($this->output)) {
            return $this->output;
        }
        return null;
    }

    public function getHourlyInputVolume() {
        if (!isset($this->inputVolume)) {
            $key = __METHOD__.":".$this->instanceID;
            if ($this->cacheMgr->isCached($key)) {
                $this->inputVolume = $this->cacheMgr->load($key);
            } else {
                if ($this->chain) {
                    $totalVolume = 0;
                    foreach ($this->inputs as $inputSet) {
                        foreach ($inputSet as $input) {
                            $inputObject = $this->factory->create(ObjectFactory::TYPE, $input['typeID']);
                            $inputVolume = $inputObject->getVolume() * $input['inputQty'];
                            $totalVolume += $inputVolume;
                        }
                    }
                } else {
                    $totalVolume = $this->reaction->getInputVolume();
                }
                $this->inputVolume = $totalVolume;
                $this->cacheMgr->save($key,$this->inputVolume,300);
            }
        }
        return $this->inputVolume;
    }

    public function getHourlyOutputQty() {
        if (!isset($this->outputQty)) {
            if ($this->chain) {
                $this->outputQty = 2*$this->reaction->getOutputQty();
            } else {
                $this->outputQty = $this->reaction->getOutputQty();
            }
        }
        return $this->outputVolume;
    }

    public function getHourlyOutputVolume() {
        if (!isset($this->outputVolume)) {
            if ($this->chain) {
                $this->outputVolume = 2*$this->reaction->getOutputVolume();
            } else {
                $this->outputVolume = $this->reaction->getOutputVolume();
            }
        }
        return $this->outputVolume;
    }

    public function getNumCycles() {
        if (isset($this->numCycles)) {
            return $this->numCycles;
        }
        return null;
    }

    public function getHourlyInputCost() {
        if (!isset($this->hourlyInputCost)) {
            $key = __METHOD__.":".$this->instanceID;
            if ($this->cacheMgr->isCached($key)) {
                $this->hourlyInputCost= $this->cacheMgr->load($key);
            } else {
                $totalInputCost = 0;
                if ($this->chain) {
                    foreach ($this->inputs as $inputSet) {
                        foreach ($inputSet as $input) {
                            $thisInput = $this->factory->create(ObjectFactory::TYPE, $input['typeID']);
                            $price = $thisInput->getPrice($this->systemID, $this->datetime, $this->iPrice);
                            $quantity = $input['inputQty'];
                            $inputCost = $price * $quantity;
                            $totalInputCost += $inputCost;
                        }
                    }
                } else {
                    foreach ($this->inputs as $input) {
                        $thisInput = $this->factory->create(ObjectFactory::TYPE, $input['typeID']);
                        $price = $thisInput->getPrice($this->systemID, $this->datetime, $this->iPrice);
                        $quantity = $input['inputQty'];
                        $inputCost = $price * $quantity;
                        $totalInputCost += $inputCost;
                    }
                }
                $this->hourlyInputCost = $totalInputCost;
                $this->cacheMgr->save($key,$this->hourlyInputCost,300);
            }
        }
        return $this->hourlyInputCost;
    }

    public function getHourlyRevenue() {
        if (!isset($this->hourlyRevenue)) {
            $key = __METHOD__.":".$this->instanceID;
            if ($this->cacheMgr->isCached($key)) {
                $this->hourlyRevenue = $this->cacheMgr->load($key);
            } else {
                $this->hourlyRevenue = $this->output->getPrice($this->systemID, $this->datetime, $this->oPrice) * $this->getHourlyOutputQty();
                $this->cacheMgr->save($key,$this->hourlyRevenue,300);
            }
        }
        return $this->hourlyRevenue;
    }

    /**
     * Calculates the number of large towers required for a reaction chain.  1 medium tower = .5 large tower
     * @return	float
     */
    public function getNumTowers() {
        if (!isset($this->numTowers)) {
            if ($this->chain) {
                $numSimple = 0;
                foreach ($this->inputs as $inputSet) {
                    $numSimple += count($inputSet);
                }

                if ($this->race == 2 && $numSimple == 4) {
                    $numTowers = 2;
                } else {
                    $numTowers = 2 + (float)($numSimple / 4);
                }
            } else {
                if ($this->reaction->getReactionType() == 2) {
                    $numTowers = 1;
                } else {
                    $numTowers = .5;
                }
            }
            $this->numTowers = $numTowers;
        }
        return $this->numTowers;
    }

    public function getHourlyFuelCost() {
        if (!isset($this->hourlyFuelCost)) {
            $key = __METHOD__.":".$this->instanceID;
            if ($this->cacheMgr->isCached($key)) {
                $this->hourlyFuelCost = $this->cacheMgr->load($key);
            } else {
                if ($this->sov) {
                    $sovBonus = .75;
                } else {
                    $sovBonus = 1;
                }

                $fuelBlockID = $this->dbMgr->getFuelBlockID($this->race);
                $fuelBlockType = $this->factory->create(ObjectFactory::TYPE, $fuelBlockID);
                $fuelBlockPrice = $fuelBlockType->getPrice($this->systemID, $this->datetime, $this->fPrice);
                $hourlyFuelCost = $fuelBlockPrice * ($this->getNumTowers() * (40 * $sovBonus));

                $this->hourlyFuelCost = $hourlyFuelCost;
                $this->cacheMgr->save($key, $this->hourlyFuelCost, 300);
            }
        }
        return $this->hourlyFuelCost;
    }

    public function getHourlyFuelVolume() {
        if (!isset($this->hourlyFuelVolume)) {
            if ($this->sov) {
                $sovBonus = .75;
            } else {
                $sovBonus = 1;
            }
            $hourlyFuelVolume = $this->getNumTowers() * 40 * $sovBonus * 5;

            $this->hourlyFuelVolume = $hourlyFuelVolume;
        }
        return $this->hourlyFuelVolume;
    }

    /**
     * Calculates the hourly net income (revenue minus input and fuel costs) for a given reaction or reaction chain.
     * @return	float
     */
    public function getHourlyNetIncome() {
        if (!isset($this->hourlyNetIncome)) {
            $key = __METHOD__.":".$this->instanceID;
            if ($this->cacheMgr->isCached($key)) {
                $this->hourlyNetIncome = $this->cacheMgr->load($key);
            } else {
                $inputCosts = $this->getHourlyInputCost();
                $revenue = $this->getHourlyRevenue();
                $numTowers = $this->getNumTowers();
                $fuelCost = $this->getHourlyFuelCost();
                $gsfMoonTax = 0;
                if ($this->gsf) {
                    $gsfMoonTax = ceil($numTowers) * (1000000 / 24);
                }
                $netIncome = $revenue - ($inputCosts + $fuelCost + $gsfMoonTax);

                $this->hourlyNetIncome = $netIncome;
                $this->cacheMgr->save($key,$this->hourlyNetIncome,300);
            }
        }
        return $this->hourlyNetIncome;
    }
}