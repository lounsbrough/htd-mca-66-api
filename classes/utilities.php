<?php
class Utilities 
{
    public function twoDigitHex($number) 
    {
        return str_pad(dechex($number), 2, '0', STR_PAD_LEFT);
    }

    public function getCommandNumberArray($zone, $commandCode, $dataCode) 
    {
        return array(
            2,
            0,
            $zone,
            $commandCode,
            $dataCode
        );
    }

    public function getHexCommand($zone, $commandCode, $dataCode) 
    {
        $result = '';
        $numbers = $this->getCommandNumberArray($zone, $commandCode, $dataCode);
        $checksum = 0;
        foreach ($numbers as $number) {
            $result .= $this->twoDigitHex($number);
            $checksum += $number;
        }
        $result .= $this->twoDigitHex($checksum);
        return $result;
    }

    public function getBinaryDigit($binaryData, $digit)
    {
        return substr(decbin(hexdec(bin2hex($binaryData))), $digit, 1);
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