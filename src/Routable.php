<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/13
 * Time: 9:13
 */

namespace Sue;

abstract class Routable
{
    use CallableResolverAwareTrait;

    /**
     * The callable payload
     *
     * @var callable
     */
    protected $callable;

    /**
     * Container
     *
     * @var Container
     */
    protected $container;

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Route middleware
     *
     * @var callable[]
     */
    protected $middleware = [];

    /**
     * Get the middleware registered for the group
     *
     * @return callable[]
     */
    public function getMiddleware()
    {
        return $this->middleware;
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
     * Set container for use with resolveCallable
     *
     * @param Container $container
     *
     * @return self
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Prepend middleware to the middleware collection
     *
     * @param callable|string $callable The callback routine
     *
     * @return static
     */
    public function add($callable)
    {
        $this->middleware[] = new DeferredCallable($callable, $this->container);
        return $this;
    }

    /**
     * Set the route pattern
     *
     * @param string $newPattern
     */
    public function setPattern($newPattern)
    {
        $this->pattern = $newPattern;
    }

}