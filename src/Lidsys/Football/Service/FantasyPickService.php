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

use Pdo;

use Silex\Application;

class FantasyPickService
{
    private $app;



    public function __construct(Application $app)
    {
        $this->app    = $app;
    }



    public function getPicksForWeek($year, $week_number)
    {
        $weeks = $this->app['lidsys.football.schedule']->getWeeksForYear($year);

        if (!isset($weeks[$week_number])) {
            throw new Exception\WeekNotFound($year, $week_number);
        }

        $week = $weeks[$week_number];

        $picks = array();

        $pdo   = $this->app['db']->getPdo();
        $query = $pdo->prepare(
            "
                SELECT
                    playerId AS player_id,
                    gameId AS game_id,
                    teamId AS team_id
                FROM nflFantPick pick
                JOIN nflGame game USING (gameId)
                WHERE weekId = :week_id
                    AND gameTime < :now
            "
        );
        $query->execute(array(
            'week_id' => $week['week_id'],
            'now'     => gmdate('Y-m-d H:i:s'),
        ));
        while ($pick = $query->fetch()) {
            $picks[$pick['game_id']][$pick['player_id']] = $pick;
        }

        return $picks;
    }
}