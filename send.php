<?php

require_once 'vendor/autoload.php';

use Assert\Assertion;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use StructurizrPHP\Client\Client;
use StructurizrPHP\Client\Credentials;
use StructurizrPHP\Client\Infrastructure\Http\SymfonyRequestFactory;
use StructurizrPHP\Client\UrlMap;
use StructurizrPHP\Core\Model\Enterprise;
use StructurizrPHP\Core\Model\Location;
use StructurizrPHP\Core\Model\Tags;
use StructurizrPHP\Core\View\Configuration\Shape;
use StructurizrPHP\Core\Workspace;

$workspace = new Workspace(
	$id = (string)\getenv('STRUCTURIZR_WORKSPACE_ID'),
	$name = 'DocPlanner SEARCH',
	$description = 'Model of search system'
);
$workspace->getModel()->setEnterprise(new Enterprise('DocPlanner SEARCH'));
$person = $workspace->getModel()->addPerson(
	$name = 'Patient',
	'',
	Location::internal()
);
$softwareSystem = $workspace->getModel()->addSoftwareSystem(
	$name = 'Search',
	$description = 'Doctors and facilities ranked searching',
	Location::internal()
);
$person->usesSoftwareSystem($softwareSystem, 'Searches a medical service', 'HTTP');

$contextView = $workspace->getViews()->createSystemContextView($softwareSystem, 'System Context', '[C1] A System Context diagram');
$contextView->addAllElements();
$contextView->setAutomaticLayout(true);

$styles = $workspace->getViews()->getConfiguration()->getStyles();

$styles->addElementStyle(Tags::SOFTWARE_SYSTEM)->background("#1168bd")->color('#ffffff');
$styles->addElementStyle(Tags::PERSON)->background("#08427b")->color('#ffffff')->shape(Shape::person());

$client = new Client(
	new Credentials((string)\getenv('STRUCTURIZR_API_KEY'), (string)\getenv('STRUCTURIZR_API_SECRET')),
	new UrlMap('https://api.structurizr.com'),
	new \GuzzleHttp\Client,
	new SymfonyRequestFactory,
	new Logger('structurizr', [new StreamHandler('php://stdout')])
);

$existingWorkspace = $client->get((string)\getenv('STRUCTURIZR_WORKSPACE_ID'));
$client->put($workspace);
