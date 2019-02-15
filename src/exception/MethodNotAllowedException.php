<?php
namespace Sue\Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MethodNotAllowedException extends SueException
{
    /**
     * HTTP methods allowed
     *
     * @var string[]
     */
    protected $allowedMethods;

    /**
     * Create new exception
     *
     * @param Request $request
     * @param Response $response
     * @param string[] $allowedMethods
     */
    public function __construct(Request $request, Response $response, array $allowedMethods)
    {
        parent::__construct($request, $response);
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get allowed methods
     *
     * @return string[]
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
