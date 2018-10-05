<?php
class Controller
{
    private $appSettings;

    public function __construct()
    {
        $this->appSettings = json_decode(file_get_contents('config/appSettings.json'), true);
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

    public function convertControllerVolumeToPercent($volume)
    {
        $volume = $volume == 0 ? 256 : $volume;
        $volume = round(($volume - 196.0) / 60.0 * 100.0);
        return max(0, min(100, $volume));
    }

    public function convertPercentVolumeToController($volume)
    {
        $volume = round($volume / 100.0 * 60.0 + 196.0);
        $volume = max(196, min(256, $volume));
        return $volume % 256;
    }
}
?>