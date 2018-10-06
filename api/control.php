<?php
require_once dirname(__FILE__).'/../classes/authentication.php';
$authentication = new Authentication();

$jsonBody = $authentication->getRequestJSON(file_get_contents('php://input'));
$authentication->authenticateRequest($jsonBody['authCode']);

require_once dirname(__FILE__).'/../classes/controller.php';
$controller = new Controller();

if (!isset($jsonBody['command']))
{
    throw new Exception('Command was not specified');
}
$command = trim($jsonBody['command']);

$commandsThatAcceptAllZones = array(
    'getState',
    'powerOn',
    'powerOff'
);

if (!in_array($command, $commandsThatAcceptAllZones) && !isset($jsonBody['zones']['name']) && !isset($jsonBody['zones']['number']))
{
    throw new Exception('Zone was not specified');
}

$appSettings = json_decode(file_get_contents(dirname(__FILE__).'/../config/appSettings.json'), true);
if (isset($jsonBody['zones']['number']))
{
    $zoneNumber = trim($jsonBody['zones']['number']);
    foreach ($appSettings['zones'] as $zone)
    {
        if ($zone['number'] == $zoneNumber)
        {
            $matchedZone = $zone['number'];
        }
    }
    
    if (!isset($matchedZone))
    {
        throw new Exception('Unable to find zone by number {'.$zoneNumber.'}');
    }
}
else if (isset($jsonBody['zones']['name']))
{
    $zoneName = trim($jsonBody['zones']['name'] ?? null);
    foreach ($appSettings['zones'] as $zone)
    {
        if (strcasecmp($zone['name'], $zoneName) == 0)
        {
            $matchedZone = $zone['number'];
        }
    }
    
        if (!isset($matchedZone))
    {
        throw new Exception('Unable to find zone by name {'.$zoneName.'}');
    }
}

if (!in_array($command, $commandsThatAcceptAllZones) && !isset($matchedZone))
{
    throw new Exception('Unable to determine zone for command');
}

switch ($command) {
    case 'getState':
        echo json_encode($controller->getState($matchedZone ?? null));
        break;
    case 'powerOn':
        echo $controller->setPower($matchedZone ?? null, true);
        echo isset($matchedZone) ? 'Zone powered on' : 'All zones powered on';
        break;
    case 'powerOff':
        echo $controller->setPower($matchedZone ?? null, false);
        echo isset($matchedZone) ? 'Zone powered off' : 'All zones powered off';
        break;
    case 'volumeUp':
        $newVolume = $controller->shiftVolume($matchedZone, 'up');
        echo 'Zone volume set to {'.$newVolume.'}%';
        break;
    case 'volumeDown':
        $newVolume = $controller->shiftVolume($matchedZone, 'down');
        echo 'Zone volume set to {'.$newVolume.'}%';
        break;
    case 'setVolume':
        $volumePercentage = trim($jsonBody['volume']);
        if (!isset($volumePercentage))
        {
            throw new Exception('Command {'.$command.'} requires volume as an input');
        }
        
        if ($volumePercentage < 0 || $volumePercentage > 100)
        {
            throw new Exception('Volume {'.$volumePercentage.'} is not in the valid range');
        }
        
        $newVolume = $controller->setVolume($matchedZone, $volumePercentage);
        echo 'Zone volume set to {'.$newVolume.'}%';
        break;
    default:
        throw new Exception('Command {'.$command.'} is invalid');
}
?>