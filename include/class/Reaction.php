<?php

/**
 * Represents a certain reaction.
 * 
 * The Reaction class contains details on a given reaction, including inputs and
 * outputs, quantities, volumes, and other relevant data.
 */
class Reaction {

    /**
     * @var int $reactionID The unique ID of this reaction type.
     */
    private $reactionID = null;

    /**
     * @var int $reactionType An integer representing the class of reaction.
     */
    private $reactionType = null;

    /**
     * @var string $reactionName The descriptive name of this reaction type.
     */
    private $reactionName = null;

    /**
     * @var Array $inputs An array of required inputs to perform this reaction.
     */
    private $inputs = null;

    /**
     * @var Type $output A Type object of the reaction's output.
     */
    private $output = null;

    /**
     * @var int $outputQty How many items of output are created from a single cycle.
     */
    private $outputQty = null;

    /**
     * @var int $inputVolume The total volume of inputs required for a single cycle. 
     */
    private $inputVolume = null;

    /**
     * @var int $outputVolume The total volume of output created from a single cycle. 
     */
    private $outputVolume = null;

    public function __construct($reactionID) {
        $dbMgr = new DatabaseManager(true);
        $objectData = $dbMgr->getReactionData($reactionID);
        $this->reactionID = $objectData['reactionID'];
        $this->reactionType = $objectData['reactionType'];
        $this->inputs = $dbMgr->getReactionInputs($this->reactionID);
        $objectFactory = new ObjectFactory();
        foreach ($this->inputs as $input) {
            $inputType = $objectFactory->create(ObjectFactory::TYPE, $input['typeID']);
            $inputVolume = $inputType->getVolume() * $input['inputQty'];
            $this->inputVolume += $inputVolume;
        }
        $this->output = $objectFactory->create(ObjectFactory::TYPE, $objectData['typeID']);
        $this->outputQuantity = $objectData['outputQty'];
        $this->outputVolume = $this->outputQuantity * $this->output->getVolume();
        $this->reactionName = $this->output->getName();
        if ($this->reactionType == 3) {
            $this->reactionName += " Alchemy";
        }
        $dbMgr = null;
    }

    public function __sleep() {
        return array('reactionID', 'reactionType', 'reactionName', 'inputs',
            'output', 'outputQty', 'inputVolume', 'outputVolume');
    }

    public function getReactionID() {
        return $this->reactionID;
    }

    public function getReactionType() {
        return $this->reactionType;
    }

    public function getReactionName() {
        return $this->reactionName;
    }

    public function getInputs() {
        return $this->inputs;
    }

    public function getOutput() {
        return $this->output;
    }

    public function getOutputQty() {
        return $this->outputQuantity;
    }

    public function getInputVolume() {
        return $this->inputVolume;
    }

    public function getOutputVolume() {
        return $this->outputVolume;
    }

}
