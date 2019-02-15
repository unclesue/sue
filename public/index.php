<?php
require '../vendor/autoload.php';

$app = new Sue\Application();

/*$app->add(function ($request, $response, $next) {
    echo 'BEFORE-APP';
    $response = $next($request, $response);
    echo 'AFTER-APP';

    return $response;
});*/

require '../app/config/dependencies.php';
require '../app/config/routes.php';

$app->run();