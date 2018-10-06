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
    
    public function setPower($zone, $power)
    {
        $this->sendCommandToController($this->commands->setPower($zone, $power));
    }

    public function shiftVolume($zone, $direction)
    {
        $direction = strtolower($direction);
        
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        if (!$zoneStates[$zone]['power'])
        {
            throw new Exception('Zone is not powered on');
        }

        for ($i = 1; $i <= $this->appSettings['volumeChange']['defaultIncrement']; $i++)
        {
            $this->sendCommandToController($direction == 'up' ? $this->commands->volumeUp($zone) : $this->commands->volumeDown($zone));
            usleep($this->appSettings['volumeChange']['delayMilliseconds'] * 1000);
        }

        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        return $zoneStates[$zone]['volume'];
    }
    
    public function setVolume($zone, $volumePercentage)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        if (!$zoneStates[$zone]['power'])
        {
            throw new Exception('Zone is not powered on');
        }
        
        $volumeConversionFactor = $this->appSettings['volumeParameters']['percentageConversionFactor'];
        $currentVolume = $zoneStates[$zone]['volume'];
        $totalMovement = round(($volumePercentage - $currentVolume) * $volumeConversionFactor);

        for ($i = 1; $i <= abs($totalMovement); $i++)
        {
            $this->sendCommandToController($totalMovement > 0 ? $this->commands->volumeUp($zone) : $this->commands->volumeDown($zone));
            usleep(($this->appSettings['volumeChange']['delayMilliseconds'] / max(1, abs($totalMovement) / 10)) * 1000);
        }

        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        return $zoneStates[$zone]['volume'];
    }
}
?>