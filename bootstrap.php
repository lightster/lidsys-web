<?php

require_once __DIR__ . '/the-vendor/autoload.php';

use function The\option;
use function The\service;
use The\Db;
use The\Request;
use The\Model;

option('root_dir', __DIR__);
option('views_dir', option('root_dir') . '/views');

option('request', service(function () {
    return new Request;
}));

option('db', service(function () {
    return new Db(getenv('DATABASE_URL'));
}));

option('session_save_handler', 'files');
option('session_save_path', null);

option('honeybadger', service(function () {
    return new class {
        public function notify($e)
        {
        }
    };
}));

Model::setDb(function () {
    return option('db');
});
