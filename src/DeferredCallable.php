<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/12
 * Time: 15:46
 */

namespace Sue;

use Closure;

class DeferredCallable
{
    use CallableResolverAwareTrait;

    private $callable;
    /** @var  Container */
    private $container;

    /**
     * DeferredMiddleware constructor.
     * @param callable|string $callable
     * @param Container $container
     */
    public function __construct($callable, Container $container = null)
    {
        $this->callable = $callable;
        $this->container = $container;
    }

    public function __invoke()
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        $args = func_get_args();

        return call_user_func_array($callable, $args);
    }

}