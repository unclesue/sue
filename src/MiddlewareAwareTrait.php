<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2018/12/14
 * Time: 10:46
 */
namespace Sue;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait MiddlewareAwareTrait
{
    /**
     * Tip of the middleware call stack
     *
     * @var callable
     */
    protected $tip;

    /**
     * Middleware stack lock
     *
     * @var bool
     */
    protected $middlewareLock = false;

    protected function addMiddleware(callable $callable)
    {
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if (is_null($this->tip)) {
            $this->seedMiddlewareStack();
        }
        $next = $this->tip;
        $this->tip = function (Request $request, Response $response) use ($callable, $next) {
            return call_user_func($callable, $request, $response, $next);
        };
        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @param callable $kernel The last item to run as middleware
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack(callable $kernel = null)
    {
        if (!is_null($this->tip)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }
        if ($kernel === null) {
            $kernel = $this;
        }
        $this->tip = $kernel;
    }

    /**
     * Call middleware stack
     *
     * @param  Request $request A request object
     * @param  Response      $response A response object
     *
     * @return Response
     */
    public function callMiddlewareStack(Request $request, Response $response)
    {
        if (is_null($this->tip)) {
            $this->seedMiddlewareStack();
        }
        /** @var callable $start */
        $start = $this->tip;
        $this->middlewareLock = true;
        $response = $start($request, $response);
        $this->middlewareLock = false;
        return $response;
    }

}