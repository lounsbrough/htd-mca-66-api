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
    case 'volumeUp':
        $newVolume = $controller->changeVolume($zone, 'up');
        echo 'Volume set to {'.$newVolume.'}%';
        break;
    case 'volumeDown':
        $newVolume = $controller->changeVolume($zone, 'down');
        echo 'Volume set to {'.$newVolume.'}%';
        break;
    default:
        throw new Exception('Command {'.$command.'} is invalid');
}
?>