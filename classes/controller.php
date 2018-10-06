<?php
class Controller
{
    private $appSettings;
    private $zones;
    private $commands;

    public function __construct()
    {
        $this->appSettings = json_decode(file_get_contents(dirname(__FILE__).'/../config/appSettings.json'), true);

        require_once dirname(__FILE__).'/../classes/zones.php';
        $this->zones = new Zones();

        require_once dirname(__FILE__).'/../classes/commands.php';
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

    public function shiftVolume($zone, $direction, $percentage)
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
}
?>