<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/12
 * Time: 13:07
 */
namespace Sue;

use Closure;
use Throwable;
use Exception;
use Sue\Exception\MethodNotAllowedException;
use Sue\Exception\NotFoundException;
use Sue\Exception\SueException;
use Sue\Exception\InvalidMethodException;
use FastRoute\Dispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application
{
    use MiddlewareAwareTrait;

    /**
     * Container
     *
     * @var Container
     */
    private $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Add middleware
     *
     * @param  callable|string    $callable The callback routine
     *
     * @return static
     */
    public function add($callable)
    {
        return $this->addMiddleware(new DeferredCallable($callable, $this->container));
    }

    /**
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return \Sue\Route
     */
    public function get($pattern, $callable)
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods
     * @param  string   $pattern
     * @param  callable|string
     *
     * @return \Sue\Route
     */
    public function map(array $methods, $pattern, $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        $route = $this->container->get('router')->map($methods, $pattern, $callable);
        if (is_callable([$route, 'setContainer'])) {
            $route->setContainer($this->container);
        }

        return $route;
    }

    /**
     * Route Groups
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return \Sue\RouteGroup
     */
    public function group($pattern, $callable)
    {
        /** @var RouteGroup $group */
        $group = $this->container->get('router')->pushGroup($pattern, $callable);
        $group->setContainer($this->container);
        $group($this);
        $this->container->get('router')->popGroup();
        return $group;
    }

    /**
     * Run application
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function run()
    {
        /** @var Request $request **/
        $request = $this->container->get('request');
        /** @var Response $response **/
        $response = $this->container->get('response');

        try {
            ob_start();
            $newResponse = $this->process($request, $response);
        } catch (InvalidMethodException $e) {
            $newResponse = $this->processInvalidMethod($e->getRequest(), $response);
        } finally {
            $output = ob_get_clean();
        }

        if ($newResponse instanceof Response) {
            $response = $newResponse;
        }

        if (!empty($output)) {
            $response->setContent($output);
        }

        $response->prepare($request);
        $response->send();
    }

    /**
     * Process invalid method
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function processInvalidMethod(Request $request, Response $response)
    {
        $router = $this->container->get('router');
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getBasePath());
        }

        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->getAttribute('routeInfo', [0 => Dispatcher::NOT_FOUND]);

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->handleException(
                new MethodNotAllowedException($request, $response, $routeInfo[1]),
                $request,
                $response
            );
        }

        return $this->handleException(new NotFoundException($request, $response), $request, $response);
    }

    /**
     * Process a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function process(Request $request, Response $response)
    {
        try {
            $this->callMiddlewareStack($request, $response);
        } catch (Exception $e) {
            $response = $this->handleException($e, $request, $response);
        } catch (Throwable $e) {
            $response = $this->handlePhpError($e, $request, $response);
        }

        return $response;
    }

    /**
     * Invoke application
     *
     * @param  Request $request  The most recent Request object
     * @param  Response      $response The most recent Response object
     *
     * @return Response
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function __invoke(Request $request, Response $response)
    {
        /** @var \Sue\Router $router */
        $router = $this->container->get('router');

        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->attributes->get('routeInfo');

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $router->lookupRoute($routeInfo[1]);
            return $route->run($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            if (!$this->container->has('notAllowedHandler')) {
                throw new MethodNotAllowedException($request, $response, $routeInfo[1]);
            }
            /** @var callable $notAllowedHandler */
            $notAllowedHandler = $this->container->get('notAllowedHandler');
            return $notAllowedHandler($request, $response, $routeInfo[1]);
        }

        if (!$this->container->has('notFoundHandler')) {
            throw new NotFoundException($request, $response);
        }
        /** @var callable $notFoundHandler */
        $notFoundHandler = $this->container->get('notFoundHandler');
        return $notFoundHandler($request, $response);
    }

    /**
     * Dispatch the router to find the route. Prepare the route for use.
     *
     * @param Request $request
     * @param Router $router
     * @return Request
     */
    protected function dispatchRouterAndPrepareRoute(Request $request, Router $router)
    {
        $routeInfo = $router->dispatch($request);

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[2] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }

            $route = $router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routeArguments);

            // add route to the request's attributes in case a middleware or handler needs access to the route
            $request->attributes->set('route', $route);
        }

        $request->attributes->set('routeInfo', $routeInfo);

        return $request;
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Exception $e
     * @param  Request $request
     * @param  Response $response
     *
     * @return Response
     * @throws Exception if a handler is needed and not found
     */
    protected function handleException(Exception $e, Request $request, Response $response)
    {
        if ($e instanceof MethodNotAllowedException) {
            $handler = 'notAllowedHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e->getAllowedMethods()];
        } elseif ($e instanceof NotFoundException) {
            $handler = 'notFoundHandler';
            $params = [$e->getRequest(), $e->getResponse(), $e];
        } elseif ($e instanceof SueException) {
            // This is a Stop exception and contains the response
            return $e->getResponse();
        } else {
            // Other exception, use $request and $response params
            $handler = 'errorHandler';
            $params = [$request, $response, $e];
        }

        if ($this->container->has($handler)) {
            $callable = $this->container->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        // No handlers found, so just throw the exception
        throw $e;
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  Throwable $e
     * @param  Request $request
     * @param  Response $response
     * @return Response
     * @throws Throwable
     */
    protected function handlePhpError(Throwable $e, Request $request, Response $response)
    {
        $handler = 'phpErrorHandler';
        $params = [$request, $response, $e];

        if ($this->container->has($handler)) {
            $callable = $this->container->get($handler);
            // Call the registered handler
            return call_user_func_array($callable, $params);
        }

        // No handlers found, so just throw the exception
        throw $e;
    }

}