<?php
require_once dirname(__FILE__).'/../classes/authentication.php';
$authentication = new Authentication();

require_once dirname(__FILE__).'/../classes/request-parser.php';
$requestParser = new RequestParser();

require_once dirname(__FILE__).'/../classes/controller.php';
$controller = new Controller();

$jsonBody = $authentication->getRequestJSON(file_get_contents('php://input'));
$authentication->authenticateRequest($jsonBody);

$requestParser->requestBody = $jsonBody;
$requestParser->parseRequest();

switch ($requestParser->command) {
    case 'getState':
        echo json_encode($controller->getState($requestParser->matchedZones ?? null));
        break;

    case 'powerOn':
        echo $controller->setPower($requestParser->matchedZones ?? null, true, $requestParser->exclusiveZones);
        break;

    case 'powerOff':
        echo $controller->setPower($requestParser->matchedZones ?? null, false, $requestParser->exclusiveZones);
        break;

    case 'volumeUp':
        echo $controller->shiftVolume($requestParser->matchedZones, 'up');
        break;

    case 'volumeDown':
        echo $controller->shiftVolume($requestParser->matchedZones, 'down');
        break;

    case 'setVolume':
        echo $controller->setVolume($requestParser->matchedZones, $requestParser->volumePercentage);
        break;

    case 'setSource':
        echo $controller->setSource($requestParser->matchedZones, $requestParser->matchedSource);
        break;        

    default:
        throw new Exception('Command {'.$requestParser->command.'} is invalid');
}
?>