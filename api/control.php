<?php
require_once dirname(__FILE__).'/../classes/authentication.php';
$authentication = new Authentication();

require_once dirname(__FILE__).'/../classes/request-parser.php';
$requestParser = new RequestParser();

require_once dirname(__FILE__).'/../classes/controller.php';
$controller = new Controller();

$jsonBody = $authentication->getRequestJSON(file_get_contents('php://input'));
$authentication->authenticateRequest($jsonBody['authCode']);

$requestParser->requestBody = $jsonBody;
$requestParser->parseRequest();

switch ($requestParser->command) {
    case 'getState':
        echo json_encode($controller->getState($requestParser->matchedZone ?? null));
        break;

    case 'powerOn':
        echo $controller->setPower($requestParser->matchedZone ?? null, true, $requestParser->exclusiveZone);
        break;

    case 'powerOff':
        echo $controller->setPower($requestParser->matchedZone ?? null, false, $requestParser->exclusiveZone);
        break;

    case 'volumeUp':
        echo $controller->shiftVolume($requestParser->matchedZone, 'up');
        break;

    case 'volumeDown':
        echo $controller->shiftVolume($requestParser->matchedZone, 'down');
        break;

    case 'setVolume':
        echo $controller->setVolume($requestParser->matchedZone, $requestParser->volumePercentage);
        break;

    case 'setSource':
        echo $controller->setSource($requestParser->matchedZone, $requestParser->matchedSource);
        break;        

    default:
        throw new Exception('Command {'.$requestParser->command.'} is invalid');
}
?>