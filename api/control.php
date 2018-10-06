<?php
require_once dirname(__FILE__).'/../classes/authentication.php';
$authentication = new Authentication();

$jsonBody = $authentication->getRequestJSON($_SERVER, file_get_contents("php://input"));
$authentication->authenticateRequest($jsonBody["authCode"]);

require_once dirname(__FILE__).'/../classes/controller.php';
$controller = new Controller();

$command = $jsonBody['command'];
$zone = $jsonBody['zones']['number'];
switch ($command) {
    case 'powerOn':
        $controller->setPower($zone, true);
        echo 'Zone {'.$zone.'} powered on';
        break;
    case 'powerOff':
        $controller->setPower($zone, false);
        echo 'Zone {'.$zone.'} powered off';
        break;
    case 'volumeUp':
        $newVolume = $controller->shiftVolume($zone, 'up');
        echo 'Zone {'.$zone.'} volume set to {'.$newVolume.'}%';
        break;
    case 'volumeDown':
        $newVolume = $controller->shiftVolume($zone, 'down');
        echo 'Zone {'.$zone.'} volume set to {'.$newVolume.'}%';
        break;
    case 'setVolume':
        $volumePercentage = $jsonBody['volume'];
        if (!$volumePercentage)
        {
            throw new Exception('Command {'.$command.'} requires volume as an input');
        }
        
        $newVolume = $controller->setVolume($zone, $volumePercentage);
        echo 'Zone {'.$zone.'} volume set to {'.$newVolume.'}%';
        break;
    default:
        throw new Exception('Command {'.$command.'} is invalid');
}
?>