<?php
class AppSettings
{
    public $allSettings;
    public $enabledZones;
    public $enabledSources;

    public function __construct()
    {
        $this->allSettings = json_decode(file_get_contents(dirname(__FILE__).'/../config/appSettings.json'), true);
        $this->loadZones();
        $this->loadSources();
    }

    public function loadZones()
    {
        $this->enabledZones = array_filter($this->allSettings['zones'], function($definedZone) {
            return $definedZone['enabled'];
        });
    }

    public function loadSources()
    {
        $this->enabledSources = array_filter($this->allSettings['sources'], function($definedSource) {
            return $definedSource['enabled'];
        });
    }
}
?>