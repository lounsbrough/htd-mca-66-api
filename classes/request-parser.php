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
        if (empty($this->requestBody['command']))
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

        if (empty($this->requestBody['zones']))
        {
            if (!in_array($this->command, $commandsThatAcceptAllZones))
            {
                throw new Exception('Zones were not specified');
            }

            return;
        }

        $this->matchedZones = array();
        $this->findMatchingZones();

        if (!in_array($this->command, $commandsThatAcceptAllZones) && empty($this->matchedZones))
        {
            throw new Exception('Unable to find any zones for command');
        }

        if (!empty($this->requestBody['zones']) && empty($this->matchedZones))
        {
            throw new Exception('Zone parameter specified but no matching zones found');
        }

        $this->exclusiveZones = $this->requestBody['exclusiveZones'] ?? false;
    }

    private function findMatchingZones()
    {
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
    }

    private function validateSource()
    {
        if ($this->command == 'setSource')
        {
            if (empty($this->requestBody['source']))
            {
                throw new Exception('Command {'.$this->command.'} requires source as an input');
            }

            $this->findMatchingSource();

            if (!isset($this->matchedSource))
            {
                throw new Exception('Unable to find any sources for command');
            }
        }
    }

    private function findMatchingSource()
    {
        $source = $this->requestBody['source'];
        foreach ($this->appSettings->enabledSources as $definedSource)
        {
            if (isset($source['number']) && $definedSource['number'] == $source['number'])
            {
                $this->matchedSource = $definedSource['number'];
            }
            else if (isset($source['name']) && strcasecmp($definedSource['name'], $source['name']) == 0)
            {
                $this->matchedSource = $definedSource['number'];
            }
        }
    }

    private function validateVolume()
    {
        if ($this->command == 'setVolume')
        {
            if (empty($this->requestBody['volume']))
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