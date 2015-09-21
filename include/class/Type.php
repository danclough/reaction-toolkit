<?php

class Type {

    private $typeID, $typeName, $typeVolume, $typeDescription;

    public function __construct($typeID) {
        $dbMgr = new DatabaseManager(true);
        $objectData = $dbMgr->getTypeData($typeID);
        $this->typeID = $objectData['typeID'];
        $this->typeName = $objectData['itemName'];
        $this->typeVolume = $objectData['itemVol'];
        $this->typeDescription = $objectData['itemDesc'];
        $dbMgr = null;
    }

    public function __sleep() {
        return array('typeID', 'typeName', 'typeVolume', 'typeDescription');
    }

    public function getID() {
        return $this->typeID;
    }

    public function getName() {
        return $this->typeName;
    }

    public function getVolume() {
        return $this->typeVolume;
    }

    public function getDescription() {
        return $this->typeDescription;
    }

    public function getPrice($systemID, $datetime, $priceType) {
        $dbMgr = new DatabaseManager(true);
        $price = $dbMgr->getPrice($this->typeID, $systemID, $datetime, $priceType);
        $dbMgr = null;
        return $price;
    }

}
