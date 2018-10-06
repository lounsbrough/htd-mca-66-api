<?php
class RequestParser
{
    private $appSettings;
    public $requestBody;
    public $command;
    public $matchedZone;
    public $matchedSource;
    public $exclusiveZone;
    public $volumePercentage;

    public function __construct()
    {
        $this->appSettings = json_decode(file_get_contents(dirname(__FILE__).'/../config/appSettings.json'), true);
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

        if (!in_array($this->command, $commandsThatAcceptAllZones) && !isset($this->requestBody['zones']['name']) && !isset($this->requestBody['zones']['number']))
        {
            throw new Exception('Zone was not specified');
        }

        if (isset($this->requestBody['zones']['number']))
        {
            $zoneNumber = trim($this->requestBody['zones']['number']);
            foreach ($this->appSettings['zones'] as $definedZone)
            {
                if ($definedZone['enabled'] && $definedZone['number'] == $zoneNumber)
                {
                    $this->matchedZone = $definedZone['number'];
                }
            }
            
            if (!isset($this->matchedZone))
            {
                throw new Exception('Unable to find zone by number {'.$zoneNumber.'}');
            }
        }
        else if (isset($this->requestBody['zones']['name']))
        {
            $zoneName = trim($this->requestBody['zones']['name']);
            foreach ($this->appSettings['zones'] as $definedZone)
            {
                if ($definedZone['enabled'] && strcasecmp($definedZone['name'], $zoneName) == 0)
                {
                    $this->matchedZone = $definedZone['number'];
                }
            }
            
                if (!isset($this->matchedZone))
            {
                throw new Exception('Unable to find zone by name {'.$zoneName.'}');
            }
        }

        if (!in_array($this->command, $commandsThatAcceptAllZones) && !isset($this->matchedZone))
        {
            throw new Exception('Unable to determine zone for command');
        }

        $this->exclusiveZone = $this->requestBody['zones']['exclusive'] ?? false;
    }

    private function validateSource()
    {
        if ($this->command == 'setSource')
        {
            if (!isset($this->requestBody['source']))
            {
                throw new Exception('Command {'.$this->command.'} requires source as an input');
            }

            if (isset($this->requestBody['source']['number']))
            {
                $sourceNumber = trim($this->requestBody['source']['number']);
                foreach ($this->appSettings['sources'] as $definedSource)
                {
                    if ($definedSource['enabled'] && $definedSource['number'] == $sourceNumber)
                    {
                        $this->matchedSource = $definedSource['number'];
                    }
                }
                
                if (!isset($this->matchedSource))
                {
                    throw new Exception('Unable to find source by number {'.$sourceNumber.'}');
                }
            }
            else if (isset($this->requestBody['source']['name']))
            {
                $sourceName = trim($this->requestBody['source']['name']);
                foreach ($this->appSettings['sources'] as $definedSource)
                {
                    if ($definedSource['enabled'] && strcasecmp($definedSource['name'], $sourceName) == 0)
                    {
                        $this->matchedSource = $definedSource['number'];
                    }
                }
                
                    if (!isset($this->matchedSource))
                {
                    throw new Exception('Unable to find source by name {'.$sourceName.'}');
                }
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