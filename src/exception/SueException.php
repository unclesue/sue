<?php
namespace Sue\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stop Exception
 *
 * This Exception is thrown when the Slim application needs to abort
 * processing and return control flow to the outer PHP script.
 */
class SueException extends Exception
{
    /**
     * A request object
     *
     * @var Request
     */
    protected $request;

    /**
     * A response object to send to the HTTP client
     *
     * @var Response
     */
    protected $response;

    /**
     * Create new exception
     *
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct();
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
