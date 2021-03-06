<?php

namespace Lidsys\Application\Controller;

use Lstr\Silex\Template\Exception\TemplateNotFound;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.asset.path']['app']  = __DIR__ . '/assets';
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/template/{controller}/{template}',
            function ($controller, $template, Application $app) {
                try {
                    return $app['lstr.template']->render("{$controller}/{$template}");
                } catch (TemplateNotFound $ex) {
                    return new Response($ex->getMessage(), 404);
                }
            }
        );
        $controllers->get('/asset/{version}/{name}', function ($version, $name, Application $app, Request $request) {
            return $app['lstr.asset.responder']->getResponse(
                $name,
                array(
                    'request' => $request,
                )
            );
        })->assert('name', '.*');

        $controllers->post('/build-number/', function (Application $app) {
            return $app->json(require __DIR__ . '/../../../../build-number.php');
        });

        $controllers->get('/', function (Application $app) {
            return $app['lstr.template']->render('index/index.phtml');
        });

        return $controllers;
    }
}
