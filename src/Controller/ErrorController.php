<?php

namespace ACSEO\AIErrorExplainedBundle\Controller;

use ACSEO\AIErrorExplainedBundle\ErrorRenderer\HtmlErrorRendererWithAISuggestion;
use Symfony\Component\HttpFoundation\Response;

class ErrorController
{
    public function show(\Throwable $exception, $logger)
    {
        $renderer = new HtmlErrorRendererWithAISuggestion(true);
        $throwable  = $renderer->renderOverride($exception);

        return new Response($throwable->getAsString(), $throwable->getStatusCode(), $throwable->getHeaders());
    }
}