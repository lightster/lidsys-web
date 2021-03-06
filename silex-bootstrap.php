<?php

require_once __DIR__ . '/vendor/autoload.php';

use Lidsys\Application\Application;

use Lstr\Silex\Asset\AssetServiceProvider;
use Lstr\Silex\Config\ConfigServiceProvider;
use Lstr\Silex\Template\TemplateServiceProvider;

use Lidsys\Application\Service\Provider as AppServiceProvider;
use Lidsys\Football\Service\Provider as FootballServiceProvider;
use Lidsys\User\Service\Provider as UserServiceProvider;

use function The\option;
use function The\service;
use The\Db;

option('root_dir', __DIR__);

option('db', service(function () {
    return new Db(getenv('DATABASE_URL'));
}));

$app = new Application();
$app['route_class'] = 'Lidsys\Application\Route';

$app->register(new AppServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new ConfigServiceProvider());
$app->register(new FootballServiceProvider());
$app->register(new TemplateServiceProvider());
$app->register(new UserServiceProvider());

if (isset($app['config']['debug'])) {
    $app['debug'] = $app['config']['debug'];
}

return $app;
