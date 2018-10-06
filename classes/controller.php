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

    public function sendCommandToController($command, $delayMilliseconds = null)
    {
        $hostname = $this->appSettings['controllerGateway']['hostname'];
        $port = $this->appSettings['controllerGateway']['port'];

        try
        {
            $fp = pfsockopen($hostname, $port);

            fwrite($fp, $command);

            $response = fread($fp, 2048);
        }
        catch (Exception $e) {}

        fclose($fp);

        // Ensure controller is not overwhelmed
        usleep($delayMilliseconds ?? $this->appSettings['defaultDelayMilliseconds'] * 1000);

        return $response;
    }
    
    public function getState($zone)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        return !isset($zone) ? $zoneStates : $zoneStates[$zone];
    }
    
    public function setPower($zone, $power, $exclusive = false)
    {
        $this->sendCommandToController($this->commands->setPower($zone, $power, $zone != null ? false : true));

        if ($exclusive)
        {
            foreach ($this->appSettings['zones'] as $definedZone)
            {
                if ($definedZone['enabled'] && $definedZone['number'] != $zone)
                {
                    $this->sendCommandToController($this->commands->setPower($definedZone['number'], !$power, false));
                }
            }
        }

        return $zone != null ? 'Zone {'.$zone.'} powered '.($power ? 'on' : 'off').($exclusive ? ' exclusively' : '') : 'All zones powered '.($power ? 'on' : 'off');
    }

    public function shiftVolume($zone, $direction)
    {
        $direction = strtolower($direction);

        $defaultIncrement = $this->appSettings['volumeChange']['defaultIncrement'];
        $newVolume = $this->processVolumeShift($zone, ($direction == 'up' ? 1 : -1) * $defaultIncrement);
        return 'Zone volume set to {'.$newVolume.'}%';
    }
    
    public function setVolume($zone, $volumePercentage)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
     
        $volumeConversionFactor = $this->appSettings['volumeParameters']['percentageConversionFactor'];
        $currentVolume = $zoneStates[$zone]['volume'];
        $shift = round(($volumePercentage - $currentVolume) * $volumeConversionFactor);

        $newVolume = $this->processVolumeShift($zone, $shift);
        return 'Zone volume set to {'.$newVolume.'}%';
    }
    
    public function setSource($zone, $source)
    {
        if ($zone != null) {
            $this->sendCommandToController($this->commands->setSource($zone, $source));
        } 
        else
        {
            foreach ($this->appSettings['zones'] as $definedZone)
            {
                if ($definedZone['enabled'])
                {
                    $this->sendCommandToController($this->commands->setSource($definedZone['number'], $source));
                }
            }
        }

        return ($zone != null ? 'Zone {'.$zone.'}' : 'All zones').' set to source {'.$source.'}';
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
        
        $delayMilliseconds = $this->appSettings['volumeChange']['defaultDelayMilliseconds'] / max(1, abs($shift) / 10);
        for ($i = 1; $i <= abs($shift); $i++)
        {
            $this->sendCommandToController($shift > 0 ? $this->commands->volumeUp($zone) : $this->commands->volumeDown($zone), $delayMilliseconds);
        }
        
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        return $zoneStates[$zone]['volume'];
    }
}
?>