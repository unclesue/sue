<?php
namespace Sue\Handlers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Default application not found handler.
 */
class NotFound extends AbstractHandler
{
    /**
     * Invoke not found handler
     *
     * @param  Request $request  The most recent Request object
     * @param  Response      $response The most recent Response object
     *
     * @return Response
     * @throws UnexpectedValueException
     */
    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $contentType = 'text/plain';
            $output = $this->renderPlainNotFoundOutput();
        } else {
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonNotFoundOutput();
                    break;

                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlNotFoundOutput();
                    break;

                case 'text/html':
                    $output = $this->renderHtmlNotFoundOutput($request);
                    break;

                default:
                    throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
            }
        }

        //$body = new Body(fopen('php://temp', 'r+'));
        //$body->write($output);

        $response->headers->set('Content-Type', $contentType);
        $response->setStatusCode(404)->setContent($output);

        return $response;
    }

    /**
     * Render plain not found message
     *
     * @return Response
     */
    protected function renderPlainNotFoundOutput()
    {
        return 'Not found';
    }

    /**
     * Return a response for application/json content not found
     *
     * @return Response
     */
    protected function renderJsonNotFoundOutput()
    {
        return '{"message":"Not found"}';
    }

    /**
     * Return a response for xml content not found
     *
     * @return Response
     */
    protected function renderXmlNotFoundOutput()
    {
        return '<root><message>Not found</message></root>';
    }

    /**
     * Return a response for text/html content not found
     *
     * @param  Request $request  The most recent Request object
     *
     * @return Response
     */
    protected function renderHtmlNotFoundOutput(Request $request)
    {
        $homeUrl = $request->getSchemeAndHttpHost();
        return <<<END
<html>
    <head>
        <title>Page Not Found</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
            strong{
                display:inline-block;
                width:65px;
            }
        </style>
    </head>
    <body>
        <h1>Page Not Found</h1>
        <p>
            The page you are looking for could not be found. Check the address bar
            to ensure your URL is spelled correctly. If all else fails, you can
            visit our home page at the link below.
        </p>
        <a href='$homeUrl'>Visit the Home Page</a>
    </body>
</html>
END;
    }
}
