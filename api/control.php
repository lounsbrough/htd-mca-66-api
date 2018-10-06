<?php
require_once dirname(__FILE__).'/../classes/authentication.php';
$authentication = new Authentication();

$jsonBody = $authentication->getRequestJSON(file_get_contents('php://input'));
$authentication->authenticateRequest($jsonBody['authCode']);

require_once dirname(__FILE__).'/../classes/controller.php';
$controller = new Controller();

$command = trim($jsonBody['command']);
if (!isset($command))
{
    throw new Exception('Command was not specified');
}

$zoneDescription = trim($jsonBody['zones']['name']);
if (!isset($zoneDescription))
{
    throw new Exception('Zone was not specified');
}

$appSettings = json_decode(file_get_contents(dirname(__FILE__).'/../config/appSettings.json'), true);

foreach ($appSettings['zones'] as $zone)
{
    if (strtolower($zone['description']) == strtolower($zoneDescription))
    {
        $zoneNumber = $zone['number'];
    }
}

if (!isset($zoneNumber))
{
    throw new Exception('Unable to find zone {'.$zoneDescription.'}');
}

switch ($command) {
    case 'powerOn':
        $controller->setPower($zoneNumber, true);
        echo 'Zone powered on';
        break;
    case 'powerOff':
        $controller->setPower($zoneNumber, false);
        echo 'Zone powered off';
        break;
    case 'volumeUp':
        $newVolume = $controller->shiftVolume($zoneNumber, 'up');
        echo 'Zone volume set to {'.$newVolume.'}%';
        break;
    case 'volumeDown':
        $newVolume = $controller->shiftVolume($zoneNumber, 'down');
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
        
        $newVolume = $controller->setVolume($zoneNumber, $volumePercentage);
        echo 'Zone volume set to {'.$newVolume.'}%';
        break;
    default:
        throw new Exception('Command {'.$command.'} is invalid');
}
?>