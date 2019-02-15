<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2019/2/14
 * Time: 11:29
 */

namespace Sue;

/**
 * ResolveCallable
 *
 * @property Container $container
 */
trait CallableResolverAwareTrait
{
    /**
     * Resolve a string of the format 'class:method' into a closure that the
     * router can dispatch.
     *
     * @param callable|string $callable
     *
     * @return \Closure
     *
     * @throws \RuntimeException If the string cannot be resolved as a callable
     */
    protected function resolveCallable($callable)
    {
        if (!$this->container instanceof Container) {
            return $callable;
        }

        /** @var CallableResolver $resolver */
        $resolver = $this->container->get('callableResolver');

        return $resolver->resolve($callable);
    }

}