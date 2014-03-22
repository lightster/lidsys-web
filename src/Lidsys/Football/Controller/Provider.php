<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Football\Controller;

use DateTime;
use DateTimeZone;

use Lstr\Silex\Template\Exception\TemplateNotFound;
use Lstr\Silex\Controller\JsonRequestMiddlewareService;

use Lidsys\Football\Service\Provider as FootballServiceProvider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get('/seasons', function (Application $app) {
            $seasons = $app['lidsys.football.schedule']->getSeasons();

            array_walk(
                $seasons,
                function (array & $season) {
                    unset($season['season_id']);
                }
            );

            return $app->json(array(
                'seasons' => $seasons,
            ));
        });

        $controllers->get('/weeks/{year}', function ($year, Application $app) {
            $weeks = $app['lidsys.football.schedule']->getWeeksForYear($year);

            array_walk(
                $weeks,
                function (array & $week) {
                    unset($week['week_id']);
                }
            );

            return $app->json(array(
                'weeks' => $weeks,
            ));
        });

        $controllers->get('/schedule/{year}/{week}', function ($year, $week, Application $app) {
            $games = $app['lidsys.football.schedule']->getGamesForWeek($year, $week);

            $timezone = new DateTimeZone('UTC');

            array_walk(
                $games,
                function (array & $game) use ($timezone) {
                    $start_time = new DateTime($game['start_time'], $timezone);
                    $game['start_time'] = $start_time->format('c');
                }
            );

            return $app->json(array(
                'games' => $games,
            ));
        });

        $controllers->get('/teams', function (Application $app) {
            $teams = $app['lidsys.football.team']->getTeams();

            return $app->json(array(
                'teams' => $teams,
            ));
        });

        $controllers->get('/team-standings/{year}/{week}', function ($year, $week, Application $app) {
            $team_standings = $app['lidsys.football.team']->getStandingsForWeek($year, $week);

            return $app->json(array(
                'team_standings' => $team_standings,
            ));
        });

        $controllers->get('/fantasy-picks/{year}/{week}', function ($year, $week, Application $app) {
            $picks = $app['lidsys.football.fantasy-pick']->getPicksForWeek($year, $week);

            return $app->json(array(
                'fantasy_picks' => $picks,
            ));
        });

        $controllers->get('/fantasy-players/{year}', function ($year, Application $app) {
            $players = $app['lidsys.football.fantasy-player']->getPlayersForYear($year);

            return $app->json(array(
                'fantasy_players' => $players,
            ));
        });

        $controllers->before(new JsonRequestMiddlewareService());
        $controllers->before(function (Request $request, Application $app) {
            $app->register(new FootballServiceProvider());
        });

        return $controllers;
    }
}
