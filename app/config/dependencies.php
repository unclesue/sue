<?php
// DIC configuration

$container = $app->getContainer();

$container['router'] = function ($container) {
    $router = new Sue\Router();
    if (method_exists($router, 'setContainer')) {
        $router->setContainer($container);
    }

    return $router;
};

$container['request'] = function () {
    return Symfony\Component\HttpFoundation\Request::createFromGlobals();
};

$container['response'] = function () {
    return new Symfony\Component\HttpFoundation\Response(
        'Content',
        Symfony\Component\HttpFoundation\Response::HTTP_OK,
        array('content-type' => 'text/html')
    );
};

$container['phpErrorHandler'] = function ($container) {
    return new \Sue\Handlers\PhpError(/*$container->get('settings')['displayErrorDetails']*/);
};

$container['errorHandler'] = function ($container) {
    return new \Sue\Handlers\Error(/*$container->get('settings')['displayErrorDetails']*/);
};

$container['notFoundHandler'] = function () {
    return new \Sue\Handlers\NotFound;
};

$container['notAllowedHandler'] = function () {
    return new \Sue\Handlers\NotAllowed;
};

$container['callableResolver'] = function ($container) {
    return new Sue\CallableResolver($container);
};