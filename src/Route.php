<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/12
 * Time: 13:08
 */
namespace Sue;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Route extends Routable
{
    use MiddlewareAwareTrait;

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Parent route groups
     *
     * @var RouteGroup[]
     */
    protected $groups;

    private $finalized = false;

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Route arguments parameters
     *
     * @var null|array
     */
    protected $savedArguments = [];

    /**
     * Create new route
     *
     * @param string|string[] $methods The route HTTP methods
     * @param string $pattern The route pattern
     * @param callable $callable The route callable
     * @param RouteGroup[] $groups The parent route groups
     * @param int $identifier The route identifier
     */
    public function __construct($methods, $pattern, $callable, $groups = [], $identifier = 0)
    {
        $this->methods = is_string($methods) ? [$methods] : $methods;
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->groups = $groups;
        $this->identifier = 'route' . $identifier;
    }

    /**
     * Finalize the route in preparation for dispatching
     */
    public function finalize()
    {
        if ($this->finalized) {
            return;
        }

        $groupMiddleware = [];
        foreach ($this->getGroups() as $group) {
            $groupMiddleware = array_merge($group->getMiddleware(), $groupMiddleware);
        }

        $this->middleware = array_merge($this->middleware, $groupMiddleware);

        foreach ($this->getMiddleware() as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->finalized = true;
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get route identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get the route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Run route
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function run(Request $request, Response $response)
    {
        $this->finalize();

        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * Prepare the route for use
     *
     * @param Request $request
     * @param array $arguments
     */
    public function prepare($request, array $arguments)
    {
        // Remove temp arguments
        $this->setArguments($this->savedArguments);

        // Add the route arguments
        foreach ($arguments as $k => $v) {
            $this->setArgument($k, $v, false);
        }
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     * @param bool $includeInSavedArguments
     *
     * @return self
     */
    public function setArgument($name, $value, $includeInSavedArguments = true)
    {
        if ($includeInSavedArguments) {
            $this->savedArguments[$name] = $value;
        }
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     * @param bool $includeInSavedArguments
     *
     * @return self
     */
    public function setArguments(array $arguments, $includeInSavedArguments = true)
    {
        if ($includeInSavedArguments) {
            $this->savedArguments = $arguments;
        }
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Dispatch route callable
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function __invoke(Request $request, Response $response)
    {
        $this->callable = $this->resolveCallable($this->callable);

        $handler = new RequestResponse();

        $handler($this->callable, $request, $response, $this->arguments);
    }

}