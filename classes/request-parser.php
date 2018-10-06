<?php
class RequestParser
{
    private $appSettings;
    public $requestBody;
    public $command;
    public $matchedZones;
    public $matchedSource;
    public $exclusiveZones;
    public $volumePercentage;

    public function __construct()
    {
        require_once dirname(__FILE__).'/app-settings.php';
        $this->appSettings = new AppSettings();
    }

    public function parseRequest()
    {
        $this->validateCommand();
        $this->validateZones();
        $this->validateSource();
        $this->validateVolume();
    }

    private function validateCommand()
    {
        if (!isset($this->requestBody['command']))
        {
            throw new Exception('Command was not specified');
        }
        
        $this->command = trim($this->requestBody['command']);
    }

    private function validateZones()
    {
        $commandsThatAcceptAllZones = array(
            'getState',
            'powerOn',
            'powerOff',
            'setSource'
        );

        if (!isset($this->requestBody['zones']))
        {
            if (!in_array($this->command, $commandsThatAcceptAllZones))
            {
                throw new Exception('Zones were not specified');
            }

            return;
        }

        $this->matchedZones = array();
        foreach ($this->requestBody['zones'] as $zone)
        {
            foreach ($this->appSettings->enabledZones as $definedZone)
            {
                if (isset($zone['number']) && $definedZone['number'] == $zone['number'])
                {
                    $this->matchedZones[] = $definedZone['number'];
                }
                else if (isset($zone['name']) && strcasecmp($definedZone['name'], $zone['name']) == 0)
                {
                    $this->matchedZones[] = $definedZone['number'];
                }
            }
        }
        
        if (!in_array($this->command, $commandsThatAcceptAllZones) && $this->matchedZones == array())
        {
            throw new Exception('Unable to find any zones for command');
        }

        $this->exclusiveZones = $this->requestBody['exclusiveZones'] ?? false;
    }

    private function validateSource()
    {
        if ($this->command == 'setSource')
        {
            if (!isset($this->requestBody['source']))
            {
                throw new Exception('Command {'.$this->command.'} requires source as an input');
            }

            $requestSource = $this->requestBody['source'];
            foreach ($this->appSettings->enabledSources as $definedSource)
            {
                if (isset($requestSource['number']) && $definedSource['number'] == $requestSource['number'])
                {
                    $this->matchedSource = $definedSource['number'];
                }
                else if (isset($requestSource['name']) && strcasecmp($definedSource['name'], $requestSource['name']) == 0)
                {
                    $this->matchedSource = $definedSource['number'];
                }
            }

            if (!isset($this->matchedSource))
            {
                throw new Exception('Unable to find any zones for command');
            }
        }
    }

    private function validateVolume()
    {
        if ($this->command == 'setVolume')
        {
            if (!isset($this->requestBody['volume']))
            {
                throw new Exception('Command {'.$this->command.'} requires volume as an input');
            }

            $this->volumePercentage = trim($this->requestBody['volume']);
            if ($this->volumePercentage < 0 || $this->volumePercentage > 100)
            {
                throw new Exception('Volume {'.$this->volumePercentage.'} is not in the valid range');
            }
        }
    }
}
?>