<?php
class Controller
{
    private $appSettings;
    private $utilities;
    private $zones;
    private $commands;

    public function __construct()
    {
        $this->appSettings = json_decode(file_get_contents(dirname(__FILE__).'/../config/appSettings.json'), true);

        require_once dirname(__FILE__).'/utilities.php';
        $this->utilities = new Utilities();
        
        require_once dirname(__FILE__).'/zones.php';
        $this->zones = new Zones();

        require_once dirname(__FILE__).'/commands.php';
        $this->commands = new Commands();
    }

    public function sendCommandToController($command)
    {
        $hostname = $this->appSettings['controllerGateway']['hostname'];
        $port = $this->appSettings['controllerGateway']['port'];

        $fp = pfsockopen($hostname, $port);

        fwrite($fp, $command);

        $response = fread($fp, 2048);

        fclose($fp);

        return $response;
    }
    
    public function getState($zone)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        return !isset($zone) ? $zoneStates : $zoneStates[$zone];
    }
    
    public function setPower($zone, $power)
    {
        $this->sendCommandToController($this->commands->setPower($zone, $power, $zone != null ? false : true));
    }

    public function shiftVolume($zone, $direction)
    {
        $direction = strtolower($direction);

        return $this->processVolumeShift($zone, ($direction == 'up' ? 1 : -1) * $this->appSettings['volumeChange']['defaultIncrement']);
    }
    
    public function setVolume($zone, $volumePercentage)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
     
        $volumeConversionFactor = $this->appSettings['volumeParameters']['percentageConversionFactor'];
        $currentVolume = $zoneStates[$zone]['volume'];
        $shift = round(($volumePercentage - $currentVolume) * $volumeConversionFactor);

        return $this->processVolumeShift($zone, $shift);
    }
    
    private function processVolumeShift($zone, $shift)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        if (!$zoneStates[$zone]['power'])
        {
            throw new Exception('Zone is not powered on');
        }
        
        if ($zoneStates[$zone]['volume'] == '')
        {
            $shift += 1;
        }
        
        for ($i = 1; $i <= abs($shift); $i++)
        {
            $this->sendCommandToController($shift > 0 ? $this->commands->volumeUp($zone) : $this->commands->volumeDown($zone));
            usleep(($this->appSettings['volumeChange']['defaultDelayMilliseconds'] / max(1, abs($shift) / 10)) * 1000);
        }
        
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        return $zoneStates[$zone]['volume'];
    }
}
?>