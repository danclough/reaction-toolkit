<?php
class Type {
    private $typeID, $db, $name, $volume, $description;

    public function __construct($typeID)
    {
        $this->typeID = $typeID;
        $this->db = new Database();

        $row = $this->db->getItemData($this->typeID);
        if (isset($row)) {
            $this->name = $row['itemName'];
            $this->volume = $row['itemVol'];
            $this->description = $row['itemDesc'];
        }
    }

    public function getTypeID() {
        return $this->typeID;
    }

    public function getName() {
        return $this->name;
    }

    public function getVolume() {
        return $this->volume;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPrice($systemID,$datetime,$priceType,$loopCount = 0) {
            return $this->db->getPrice($this->typeID,$systemID,$datetime,$priceType,$loopCount);
    }
} 