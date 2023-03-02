<?php

namespace ACSEO\AIErrorExplainedBundle\ErrorRenderer;

use ACSEO\AIErrorExplainedBundle\Solution\OpenAiSolution;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Debug\FileLinkFormatter;
use Psr\Log\LoggerInterface;

class HtmlErrorRendererWithAISuggestion extends HtmlErrorRenderer
{
    public const OPENAI_CLIENT_KEY = 'OPENAI_CLIENT_KEY';
    private $solutioner = false;

    public function __construct(bool|callable $debug = false, string $charset = null, string|FileLinkFormatter $fileLinkFormat = null, string $projectDir = null, string|callable $outputBuffer = '', LoggerInterface $logger = null)
    {
        parent::__construct($debug, $charset, $fileLinkFormat, $projectDir, $outputBuffer, $logger);
        if (isset($_ENV[self::OPENAI_CLIENT_KEY])) {
            $this->solutioner = new OpenAiSolution($_ENV[self::OPENAI_CLIENT_KEY]);
        }
    }

    public function renderOverride(\Throwable $exception) : FlattenException
    {
        if (!$this->solutioner) {
            return parent::render($exception);
        }

        $throwable = $exception;
        $errorRenderer = new HtmlErrorRenderer(true);
        $exception = $errorRenderer->render($exception);
     
        $solution = $this->solutioner->handle($throwable);
        $solutionHTML = $this->solutioner->renderSolution($solution);
        $originalHTML = $exception->getAsString();

        $firstTraceNode = '<div class="trace trace-as-html" id="trace-box-1">';
    
        $modifiedHTML = str_replace(
            $firstTraceNode,
            $solutionHTML.$firstTraceNode,
            $originalHTML
        );

        return $exception->setAsString($modifiedHTML);
    }
}