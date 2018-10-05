<?php
require_once 'classes/controller.php';
$controller = new Controller();

require_once 'classes/commands.php';
$commands = new Commands();

require_once 'classes/zones.php';
$zones = new Zones();

echo '<pre>';
print_r($zones->parseZoneState($controller->sendCommandToController($commands->getZoneState(3))));
echo '</pre>';
?>