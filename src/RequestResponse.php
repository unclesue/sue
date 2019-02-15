<?php
/**
 * Created by PhpStorm.
 * User: Sue
 * Date: 2019/2/14
 * Time: 13:14
 */

namespace Sue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestResponse
{
    /**
     * Invoke a route callable with request, response, and all route parameters
     * as an array of arguments.
     *
     * @param array|callable         $callable
     * @param Request $request
     * @param Response $response
     * @param array                  $routeArguments
     *
     * @return mixed
     */
    public function __invoke(callable $callable, Request $request, Response $response, array $routeArguments)
    {
        foreach ($routeArguments as $k => $v) {
            $request->attributes->set($k, $v);
        }

        return call_user_func($callable, $request, $response, $routeArguments);
    }
}