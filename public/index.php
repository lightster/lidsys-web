<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Lidsys\Application\Application;
use Lidsys\Application\Controller\Provider as AppControllerProvider;
use Lidsys\Football\Controller\Provider as FootballControllerProvider;
use Lidsys\User\Controller\Provider as UserControllerProvider;

use Lstr\Silex\Asset\AssetServiceProvider;
use Lstr\Silex\Config\ConfigServiceProvider;
use Lstr\Silex\Database\DatabaseServiceProvider;
use Lstr\Silex\Template\TemplateServiceProvider;

use Lidsys\User\Service\Provider as UserServiceProvider;

use Silex\Provider\SessionServiceProvider;

$app = new Application();
$app['route_class'] = 'Lidsys\Application\Route';

$app->register(new AssetServiceProvider());
$app->register(new ConfigServiceProvider());
$app->register(new DatabaseServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new TemplateServiceProvider());
$app->register(new UserServiceProvider());

$app['config'] = $app['lstr.config']->load(array(
    __DIR__ . '/../config/autoload/*.global.php',
    __DIR__ . '/../config/autoload/*.local.php',
));

$app['db'] = $app['lstr.db'](function (Application $app) {
    return $app['config']['db.config'];
});

if (isset($app['config']['debug'])) {
    $app['debug'] = $app['config']['debug'];
}

$app->mount('/api/v1.0/football', new FootballControllerProvider());
$app->mount('/app/user', new UserControllerProvider());
$app->mount('/app', new AppControllerProvider());

$app->get('/', function () use ($app) {
    return $app->redirect('/app/');
});


$app->run();
