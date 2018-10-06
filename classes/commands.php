<?php
class Commands 
{
    private $utilities;

    public function __construct() 
    {
        require_once dirname(__FILE__).'/utilities.php';
        $this->utilities = new Utilities();
    }
    
    public function getCommandBytes($zone, $commandCode, $dataCode)
    {
        return hex2bin($this->utilities->getHexCommand($zone, $commandCode, $dataCode));
    }

    public function getZoneStates() 
    {
        return $this->getCommandBytes(1, 6, 0);
    }    

    public function setPower($zone, $power, $allZones = false)
    {
        return $this->getCommandBytes((!$allZones ? $zone : 1), 4, $power ? (!$allZones ? 32 : 56) : (!$allZones ? 33 : 57));
    }
    
    public function volumeUp($zone)
    {
        return $this->getCommandBytes($zone, 4, 9);
    }
    
    public function volumeDown($zone)
    {
        return $this->getCommandBytes($zone, 4, 10);
    }
    
    public function mute($zone) 
    {
        return $this->getCommandBytes($zone, 4, 34);
    }
    
    public function setSource($zone, $source) 
    {
        return $this->getCommandBytes($zone, 4, 2 + $source);
    }

    public function bassUp($zone) 
    {
        return $this->getCommandBytes($zone, 4, 38);
    }

    public function bassDown($zone) 
    {
        return $this->getCommandBytes($zone, 4, 39);
    }

    public function trebleUp($zone) 
    {
        return $this->getCommandBytes($zone, 4, 40);
    }

    public function trebleDown($zone) 
    {
        return $this->getCommandBytes($zone, 4, 41);
    }

    public function balanceRight($zone) 
    {
        return $this->getCommandBytes($zone, 4, 42);
    }

    public function balanceLeft($zone) 
    {
        return $this->getCommandBytes($zone, 4, 43);
    }
}
?>