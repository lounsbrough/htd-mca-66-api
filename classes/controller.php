<?php
class Controller
{
    private $appSettings;
    private $utilities;
    private $zones;
    private $commands;

    public function __construct()
    {
        require_once dirname(__FILE__).'/app-settings.php';
        $this->appSettings = new AppSettings();

        require_once dirname(__FILE__).'/utilities.php';
        $this->utilities = new Utilities();
        
        require_once dirname(__FILE__).'/zones.php';
        $this->zones = new Zones();

        require_once dirname(__FILE__).'/commands.php';
        $this->commands = new Commands();
    }

    public function sendCommandToController($command, $delayMilliseconds = null)
    {
        $hostname = $this->appSettings->allSettings['controllerGateway']['hostname'];
        $port = $this->appSettings->allSettings['controllerGateway']['port'];

        try
        {
            $fp = pfsockopen($hostname, $port);

            fwrite($fp, $command);

            $response = fread($fp, 2048);
        }
        catch (Exception $e) {}

        fclose($fp);

        // Ensure controller is not overwhelmed
        usleep($delayMilliseconds ?? $this->appSettings->allSettings['defaultDelayMilliseconds'] * 1000);

        return $response;
    }
    
    public function getState($zones)
    {        
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));

        if ($zones != null) {
            foreach ($zoneStates as $key => $zoneState)
            {
                if (!in_array($zoneState['number'], $zones)) {
                    unset($zoneStates[$key]);
                }
            }
        }

        return $zoneStates;
    }
    
    public function setPower($zones, $power, $exclusive = false)
    {
        foreach ($zones as $zone) 
        {
            $this->sendCommandToController($this->commands->setPower($zone, $power, $zone != null ? false : true));
        }

        if ($exclusive)
        {
            foreach ($this->appSettings->enabledZones as $definedZone)
            {
                if (!in_array($definedZone['number'], $zones))
                {
                    $this->sendCommandToController($this->commands->setPower($definedZone['number'], !$power, false));
                }
            }
        }

        return $zones != null ? 'Zones {'.implode($zones, ',').'} powered '.($power ? 'on' : 'off').($exclusive ? ' exclusively' : '') : 'All zones powered '.($power ? 'on' : 'off');
    }

    public function shiftVolume($zones, $direction)
    {
        $direction = strtolower($direction);
        $defaultIncrement = $this->appSettings->allSettings['volumeChange']['defaultIncrement'];
        
        $shift = ($direction == 'up' ? 1 : -1) * $defaultIncrement;
        $this->processVolumeShift($zones, array_fill(0, count($zones), $shift));

        return 'Zones {'.implode($zones, ',').'} volume turned '.$direction;
    }
    
    public function setVolume($zones, $volumePercentage)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        $volumeConversionFactor = $this->appSettings->allSettings['volumeParameters']['percentageConversionFactor'];

        $shifts = array();
        foreach ($zones as $zone)
        {
            $currentVolume = $zoneStates[$zone]['volume'];
            $shifts[] = round(($volumePercentage - $currentVolume) * $volumeConversionFactor);
        }

        $this->processVolumeShift($zones, $shifts);

        return 'Zones {'.implode($zones, ',').'} volume set to {'.$volumePercentage.'}%';
    }
    
    public function setSource($zones, $source)
    {
        if ($zones != null) {
            foreach ($zones as $zone)
            {
                $this->sendCommandToController($this->commands->setSource($zone, $source));
            }
        } 
        else
        {
            $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
            foreach ($this->appSettings->enabledZones as $definedZone)
            {
                $zonePoweredOn = $zoneStates[$definedZone['number']]['power'];
                
                if (!$zonePoweredOn) $this->sendCommandToController($this->commands->setPower($definedZone['number'], true));
                $this->sendCommandToController($this->commands->setSource($definedZone['number'], $source));
                if (!$zonePoweredOn) $this->sendCommandToController($this->commands->setPower($definedZone['number'], false));
            }
        }

        return ($zones != null ? 'Zones {'.implode($zones, ',').'}' : 'All zones').' set to source {'.$source.'}';
    }

    private function processVolumeShift($zones, $shifts)
    {
        $zoneStates = $this->zones->parseZoneState($this->sendCommandToController($this->commands->getZoneStates()));
        $defaultDelayMilliseconds =$this->appSettings->allSettings['volumeChange']['defaultDelayMilliseconds'];

        foreach ($shifts as $shift)
        {
            $delayMilliseconds[] = $defaultDelayMilliseconds / max(1, abs($shift) / 10);
        }

        while (max(array_map('abs', $shifts)) > 0) {
            foreach ($zones as $key => $zone)
            {
                if ($shifts[$key] == 0)
                {
                    continue;
                }

                if (!$zoneStates[$zone]['power'])
                {
                    $shifts[$key] = 0;
                    continue;
                }

                $this->sendCommandToController($shifts[$key] > 0 ? $this->commands->volumeUp($zone) : $this->commands->volumeDown($zone), $delayMilliseconds[$key]);

                $shifts[$key] += ($shifts[$key] > 0 ? -1 : 1);
            }
        }
    }
}
?>