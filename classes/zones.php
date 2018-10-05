<?php
class Zones
{
    private $controller;
    private $utilities;

    public function __construct() 
    {
        require_once dirname(__FILE__).'/utilities.php';
        $this->utilities = new Utilities();
    }

    public function parseZoneState($binaryData)
    {
        $zonesConfig = array();

        $i = 0;
        while ($i + 14 <= strlen($binaryData))
        {
            $zoneData = substr($binaryData, $i, $i + 14);

            if (bin2hex($zoneData[0]) == 0x02 && bin2hex($zoneData[1]) == 0x00)
            {
                if (hexdec(bin2hex($zoneData[3])) == 5)
                {
                    $zoneConfig = array();
                    $zoneConfig['number'] = hexdec(bin2hex($zoneData[2]));
                    $zoneConfig['power'] = $this->utilities->getBinaryDigit($zoneData[4], 0) == 1 ? true : false; // 0b[1]0000000
                    $zoneConfig['mute'] = $this->utilities->getBinaryDigit($zoneData[4], 1) == 1 ? true : false; // 0b0[1]000000
                    $zoneConfig['mode'] = $this->utilities->getBinaryDigit($zoneData[4], 2) == 1 ? 'attributes' : 'volume'; // 0b00[1]00000
                    $zoneConfig['party'] = $this->utilities->getBinaryDigit($zoneData[4], 3) == 1 ? true : false; // 0b000[1]0000
                    $zoneConfig['input'] = hexdec(bin2hex($zoneData[8])) + 1;
                    $zoneConfig['volume'] = $this->utilities->convertControllerVolumeToPercent(hexdec(bin2hex($zoneData[9]))); // (Range -> 196-255(0); 0 == max)
                    $zoneConfig['treble'] = hexdec(bin2hex($zoneData[10]));
                    $zoneConfig['bass'] = hexdec(bin2hex($zoneData[11]));
                    $zoneConfig['balance'] = hexdec(bin2hex($zoneData[12]));

                    $zonesConfig[$zoneConfig['number']] = $zoneConfig;
                }
            }

            $i += 14;
        }

        return $zonesConfig;
    }
}
?>