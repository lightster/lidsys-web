<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['lidsys.football.fantasy-pick'] = $app->share(function ($app) {
            return new FantasyPickService(
                $app['db'],
                $app['lidsys.football.schedule']
            );
        });
        $app['lidsys.football.fantasy-player'] = $app->share(function ($app) {
            return new FantasyPlayerService(
                $app['db']
            );
        });
        $app['lidsys.football.fantasy-standings'] = $app->share(function ($app) {
            return new FantasyStandingService(
                $app['db'],
                $app['lidsys.football.schedule']
            );
        });
        $app['lidsys.football.schedule'] = $app->share(function ($app) {
            return new ScheduleService(
                $app['db']
            );
        });
        $app['lidsys.football.team'] = $app->share(function ($app) {
            return new TeamService(
                $app['db'],
                $app['lidsys.football.schedule']
            );
        });
        $app['lidsys.football.notification'] = $app->share(function ($app) {
            return new NotificationService(
                $app['lidsys.football.schedule'],
                $app['mailer'],
                $app['lidsys.user.authenticator']
            );
        });
    }



    public function boot(Application $app)
    {
    }
}
