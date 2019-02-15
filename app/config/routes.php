<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$mw = function (Request $request, Response $response, $next) {
    echo 'BEFORE-ROUTE';
    $response = $next($request, $response);
    echo 'AFTER-ROUTE';

    return $response;
};

$app->get('/user/{name}/{id:[0-9]+}', '\App\controllers\Site:index');

$app->get('/user/{id:[0-9]+}', function () {
    echo 2;
})->add($mw);

$app->get('/user/{name}', function () {
    echo 3;
});

$app->group('/group/{id:[0-9]+}', function () {
    $this->map(['GET', 'DELETE', 'PATCH', 'PUT'], '', function () {
        // Find, delete, patch or replace user identified by $args['id']
        echo '123';
    });
    $this->get('/reset-password', function () {
        // Route for /users/{id:[0-9]+}/reset-password
        // Reset the password for user identified by $args['id']
        echo 'rest-password';
    });
});