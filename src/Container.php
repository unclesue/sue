<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/13
 * Time: 10:59
 */

namespace Sue;

use Psr\Container\ContainerInterface;
use Pimple\Container as PimpleContainer;

class Container extends PimpleContainer implements ContainerInterface
{

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    public function has($id)
    {
        return $this->offsetExists($id);
    }
}