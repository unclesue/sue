<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/12
 * Time: 13:10
 */
namespace Sue;

use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class Router
{

    /**
     * Container Interface
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Route middleware
     *
     * @var callable[]
     */
    protected $middleware = [];

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Route counter incrementer
     * @var int
     */
    protected $routeCounter = 0;

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    /**
     * Path to fast route cache file. Set to false to disable route caching
     *
     * @var string|False
     */
    protected $cacheFile = false;//__DIR__ . '/../runtimes/route.cache';


    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     *
     * @return Route
     *
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map($methods, $pattern, $handler)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        // According to RFC methods are defined in uppercase (See RFC 7231)
        $methods = array_map("strtoupper", $methods);

        // Add route
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  Request $request The current HTTP request object
     * @return array
     */
    public function dispatch(Request $request)
    {
        //$uri = '/' . ltrim($request->getUri()->getPath(), '/');
        $uri = $request->getRequestUri();

        return $this->createDispatcher()->dispatch($request->getMethod(), $uri);
    }

    /**
     * Create a new Route object
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $callable The route callable
     *
     * @return Route
     */
    protected function createRoute($methods, $pattern, $callable)
    {
        $route = new Route($methods, $pattern, $callable, $this->routeGroups, $this->routeCounter);
        if (!empty($this->container)) {
            $route->setContainer($this->container);
        }

        return $route;
    }

    /**
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };

        if ($this->cacheFile) {
            $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'cacheFile' => $this->cacheFile,
            ]);
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
        }

        return $this->dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Process route groups
     *
     * @return string A group pattern to prefix routes with
     */
    protected function processGroups()
    {
        $pattern = "";
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getPattern();
        }
        return $pattern;
    }

    /**
     * Add a route group to the array
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroup
     */
    public function pushGroup($pattern, $callable)
    {
        $group = new RouteGroup($pattern, $callable);
        array_push($this->routeGroups, $group);
        return $group;
    }

    /**
     * Removes the last route group from the array
     *
     * @return RouteGroup|bool The RouteGroup if successful, else False
     */
    public function popGroup()
    {
        $group = array_pop($this->routeGroups);
        return $group instanceof RouteGroup ? $group : false;
    }

    /**
     * @param $identifier
     * @return Route
     */
    public function lookupRoute($identifier)
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

}