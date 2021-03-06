<?php

namespace Lidsys\User\Controller;

use Lstr\Silex\Controller\JsonRequestMiddlewareService;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class Provider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['lstr.template.path'][] = __DIR__ . '/views';

        $controllers = $app['controllers_factory'];

        $controllers->post('/login/help/', function (Request $request, Application $app) {
            $is_found = $app['lidsys.user.auth-reset']->sendResetEmail(
                $request->get('email')
            );

            $response = [];
            if ($is_found) {
                $response['success'] = 'Your account information has been emailed to you.';
            } else {
                $response['error']   = 'The email address you provided is not registered with Lightdatasys.';
            }

            return $app->json($response);
        });

        $controllers->post('/login/reset-info/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->request->all(),
                60 * 60 * 24 // 24 hours
            );

            if ($user) {
                $user_info = [
                    'username' => $user['username'],
                ];
            } else {
                $user_info = [
                    'error' => 'invalid_token',
                ];
            }

            return $app->json($user_info);
        });

        $controllers->post('/login/reset-password/', function (Request $request, Application $app) {
            $authenticated_user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->get('authParams'),
                60 * 60 * 25 // 25 hours
            );

            if ($authenticated_user) {
                $password_change = $app['lidsys.user.authenticator']->updatePasswordForUser(
                    $authenticated_user['user_id'],
                    $request->get('newPassword')
                );
                if ($password_change) {
                    return $app->json([
                        'success' => 'Your password has successfully been changed.',
                    ]);
                }
            }

            return $app->json([
                'error' => 'invalid_token',
            ]);
        });

        $controllers->post('/login/', function (Request $request, Application $app) {
            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUsernameAndPassword(
                    $request->get('username'),
                    $request->get('password')
                );

            $response_data = [];

            unset($_SESSION['user_id']);

            if ($authenticated_user) {
                if ($authenticated_user['password_changed_at']) {
                    $response_data['authenticated_user'] = $authenticated_user;
                    $_SESSION['user_id'] = $authenticated_user['user_id'];
                } else {
                    $response_data['error'] = 'passwordless_account';
                }
            } else {
                $response_data['error'] = 'incorrect_credentials';
            }

            $response = $app->json($response_data);

            $remember_me_data = $app['lidsys.user.authenticator']->createRememberMeTokenData(
                $authenticated_user['username']
            );
            $remember_me_cookie = new Cookie(
                "remember_me",
                json_encode($remember_me_data),
                time() + (60 * 60 * 24 * 365 * 10)
            );
            $response->headers->setCookie($remember_me_cookie);

            return $response;
        });

        $controllers->post('/password/', function (Request $request, Application $app) {
            $user_id = $_SESSION['user_id'];

            $authenticated_user = false;

            if (!$request->get('currentPassword')) {
                $error = 'Your current password is required.';
            } elseif (!$request->get('newPassword')) {
                $error = 'A new password is required.';
            } elseif ($user_id) {
                $authenticated_user =
                    $app['lidsys.user.authenticator']->getUserForUserIdAndPassword(
                        $user_id,
                        $request->get('currentPassword')
                    );

                if (!$authenticated_user) {
                    $error = 'The current password you entered could not be verified.';
                } else {
                    $password_change = $app['lidsys.user.authenticator']->updatePasswordForUser(
                        $user_id,
                        $request->get('newPassword')
                    );
                    if ($password_change) {
                        return $app->json([
                            'success' => 'Your password has successfully been changed.',
                        ]);
                    } else {
                        $error = 'An error occurred. Your password change was not saved.';
                    }
                }
            } else {
                $error = 'The user you are logged in as could not be determined.';
            }

            return $app->json([
                'error' => $error,
            ]);
        });

        $controllers->post('/user-profile/color/', function (Request $request, Application $app) {
            $user_id = $_SESSION['user_id'];

            $authenticated_user =
                $app['lidsys.user.authenticator']->getUserForUserId($user_id);

            if (!$authenticated_user) {
                return $app->json([
                    'error' => 'The user you are logged in as could not be determined.',
                ]);
            }

            if ($app['lidsys.user']->updateUserColor($user_id, $request->get('background_color'))) {
                return $app->json([
                    'success' => 'Your new color has been saved.',
                ]);
            } else {
                return $app->json([
                    'error' => 'An error occurred. Your color change was not saved.',
                ]);
            }
        });

        $controllers->post('/authenticated-user/', function (Request $request, Application $app) {
            $user_id = $_SESSION['user_id'];

            $authenticated_user = false;
            $cookies = $request->cookies;

            if ($user_id) {
                $authenticated_user =
                    $app['lidsys.user.authenticator']->getUserForUserId(
                        $user_id
                    );
            } elseif ($cookies->has('remember_me')
                && ($remember_me_data = json_decode($cookies->get('remember_me'), true))
            ) {
                $authenticated_user =
                    $app['lidsys.user.authenticator']->getUserFromRememberMeTokenData(
                        $remember_me_data
                    );
                $_SESSION['user_id'] = $authenticated_user['user_id'];
            }

            return $app->json([
                'authenticated_user' => $authenticated_user,
            ]);
        });

        $controllers->post('/logout/', function (Request $request, Application $app) {
            unset($_SESSION['user_id']);

            $response = $app->json([
                'logged_out' => true,
            ]);

            $response->headers->clearCookie('remember_me');

            return $response;
        });

        $controllers->post('/register/', function (Request $request, Application $app) {
            $new_user = $app['lidsys.user']->createUser([
                'email'      => $request->get('email'),
                'first_name' => $request->get('first_name'),
                'last_name'  => $request->get('last_name'),
            ]);

            if (!empty($new_user['error'])) {
                return $app->json($new_user);
            }

            $is_found = $app['lidsys.user.auth-reset']->sendAccountSetupEmail(
                $new_user
            );
            $app['lidsys.football.notification']->sendWelcomeEmail($new_user);

            $response = [];
            if ($is_found) {
                $response['success'] = [
                    'form' => 'Your account verification email has been emailed to you.',
                ];
            } else {
                $response['error']   = [
                    'form' => 'There was an error creating your account. Please contact an administrator.',
                ];
            }

            return $app->json($response);
        });

        $controllers->post('/register/token-verification/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->request->all(),
                60 * 60 * 24 * 7 // 7 days
            );

            if ($user && empty($user['password_changed_at'])) {
                $user_info = [
                    'username' => $user['username'],
                ];
            } else {
                $user_info = [
                    'error' => 'invalid_token',
                ];
            }

            return $app->json($user_info);
        });

        $controllers->post('/register/password/', function (Request $request, Application $app) {
            $user = $app['lidsys.user.auth-reset']->getUserFromTokenQueryString(
                $request->get('authParams'),
                60 * 60 * ((24 * 7) + 1) // 7 days plus an hour
            );

            if ($user && empty($user['password_changed_at'])) {
                $password_change = $app['lidsys.user.authenticator']->updatePasswordForUser(
                    $user['user_id'],
                    $request->get('newPassword')
                );
                if ($password_change) {
                    return $app->json([
                        'success' => 'Your password has successfully been saved.',
                    ]);
                }
            }

            return $app->json([
                'error' => 'invalid_token',
            ]);
        });

        $controllers->before(new JsonRequestMiddlewareService());

        return $controllers;
    }
}
