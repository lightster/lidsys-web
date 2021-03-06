<?php

require_once __DIR__ . '/the-vendor/autoload.php';

use Honeybadger\Honeybadger;
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
option('session_save_path', '');

option('honeybadger', service(function () {
    return Honeybadger::new([
        'api_key'          => getenv('HONEYBADGER_API_KEY') ?: null,
        'environment_name' => getenv('APP_ENV') ?: 'unknown',
        'handlers'         => ['exception' => false, 'error' => false],
    ]);
}));

option('asset_buster', service(function () {
    $buster = new \The\AssetBuster(
        __DIR__ . '/asset_manifest.json',
        __DIR__ . '/public',
        [
            'sync_manifest' => getenv('APP_ENV') === 'development',
            'dirs'          => ['js', 'css'],
            'links_dirname' => 'asset_links',
        ]
    );
    $buster->initialize();

    return $buster;
}));

Model::setDb(function () {
    return option('db');
});
