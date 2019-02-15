<?php
namespace Sue\Exception;

use Symfony\Component\HttpFoundation\Request;

class InvalidMethodException extends \InvalidArgumentException
{
    protected $request;

    public function __construct(Request $request, $method)
    {
        $this->request = $request;
        parent::__construct(sprintf('Unsupported HTTP method "%s" provided', $method));
    }

    public function getRequest()
    {
        return $this->request;
    }
}
