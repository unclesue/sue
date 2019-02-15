<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/13
 * Time: 9:21
 */

namespace Sue;

use Closure;

class RouteGroup extends Routable
{

    /**
     * Create a new RouteGroup
     *
     * @param string   $pattern
     * @param callable $callable
     */
    public function __construct($pattern, $callable)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    /**
     * Invoke the group to register any Routable objects within it.
     *
     * @param Application $app The App instance to bind/pass to the group callable
     */
    public function __invoke(Application $app = null)
    {
        $callable = $this->callable;
        if ($callable instanceof Closure && $app !== null) {
            $callable = $callable->bindTo($app);
        }

        $callable($app);
    }
}