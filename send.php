<?php

require_once 'vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use StructurizrPHP\Client\Client;
use StructurizrPHP\Client\Credentials;
use StructurizrPHP\Client\Infrastructure\Http\SymfonyRequestFactory;
use StructurizrPHP\Client\UrlMap;
use StructurizrPHP\Core\Model\Enterprise;
use StructurizrPHP\Core\Model\Location;
use StructurizrPHP\Core\Model\Relationship\InteractionStyle;
use StructurizrPHP\Core\Model\Tags;
use StructurizrPHP\Core\View\Configuration\Shape;
use StructurizrPHP\Core\Workspace;

$workspace = new Workspace(
	(string)\getenv('STRUCTURIZR_WORKSPACE_ID'),
	 'DocPlanner SEARCH',
	'Model of search system'
);
$model = $workspace->getModel();
$model->setEnterprise(new Enterprise('DocPlanner SEARCH'));

# starts C1 system context diagram
$person = $model->addPerson(
	'Patient',
	'',
	Location::internal()
);
$search = $model->addSoftwareSystem(
	'Search',
	'Doctors and facilities ranked searching',
	Location::internal()
);
$person->usesSoftwareSystem($search, 'Searches a medical service', 'HTTP');

$googleAnalytics = $model->addSoftwareSystem(
	'Google Analytics',
	'',
	Location::external()
);
$search->usesSoftwareSystem($googleAnalytics, 'Sends analytics data', '');
$googleAnalytics->setTags(new Tags('External Software System'));

$algolia = $model->addSoftwareSystem(
	'Algolia',
	'',
	Location::external()
);
$search->usesSoftwareSystem($algolia, 'Asks for phrase autocomplete', '');
$algolia->usesSoftwareSystem($search, 'Returns a list of matching autocomplete items', '');
$algolia->setTags(new Tags('External Software System'));

$googleMaps = $model->addSoftwareSystem(
	'Google Maps',
	'',
	Location::external()
);
$search->usesSoftwareSystem($googleMaps, 'Asks for location autocomplete or custom coordinates', '');
$googleMaps->usesSoftwareSystem($search, 'Returns a list of matching locations, renders map', '');
$googleMaps->setTags(new Tags('External Software System'));

$views = $workspace->getViews();
$contextView = $views->createSystemContextView($search, 'System Context', '[C1] A System Context diagram');
$contextView->addAllElements();
$contextView->setAutomaticLayout(true);
# ends C1 system context diagram

$styles = $views->getConfiguration()->getStyles();
$styles->addElementStyle(Tags::SOFTWARE_SYSTEM)->background("#1168bd")->color('#ffffff');
$styles->addElementStyle('External Software System')->background("#837d8c")->color('#ffffff');
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
