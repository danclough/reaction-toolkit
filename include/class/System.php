<?php

/**
 * Represents a certain solar system.
 * 
 * The System class contains details on a given system.
 */
class System {

    /**
     * @var int $systemID The unique ID of this system.
     */
    private $systemID = null;

    /**
     * @var string $systemName The name of the system.
     */
    private $systemName = null;

    public function __construct($systemID) {
        $dbMgr = new DatabaseManager(true);
        $objectData = $dbMgr->getSystemData($systemID);
        $this->systemID = $objectData['systemID'];
        $this->systemName = $objectData['systemName'];
        $dbMgr = null;
    }
    
    public function __sleep() {
        return array('systemID', 'systemName');
    }

    public function getSystemID() {
        return $this->systemID;
    }

    public function getSystemName() {
        return $this->systemName;
    }

}
