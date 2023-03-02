<?php

namespace ACSEO\AIErrorExplainedBundle\Solution;

use OpenAI;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class OpenAiSolution
{
    protected $cache;
    
    public function __construct(protected string $openAIClientKey)
    {
        $this->cache = new FilesystemAdapter();
    }

    public function handle(\Throwable $throwable)
    {
        $prompt = $this->generatePrompt($throwable);
        $promptHash = md5($prompt);

        return $this->cache->get('openaisolution-'.$promptHash, function (ItemInterface $item) use ($prompt) {
            $item->expiresAfter(3600);
            $client = OpenAI::client($this->openAIClientKey);

            $openAIResult = $client->completions()->create([
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'max_tokens' => 100,
                'temperature' => 0,
            ]);
    
            if (isset($openAIResult->choices[0])) {
                return $openAIResult->choices[0]->text;
            }
            
            return false;
        });
    }

    private function generatePrompt(\Throwable $throwable)
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $message = $throwable->getMessage();
        $HTMLFileExcerpt = $this->fileExcerpt($file, $line);
        $snippet = str_replace("&nbsp;", " ", strip_tags($HTMLFileExcerpt));
        $snippetTab = explode("\n", $snippet);
        $cptLine = $line;
        foreach ($snippetTab as $i => $snippetLine) {
            $snippetTab[$i] = str_pad($cptLine++.": ", 5).$snippetLine;
        }

        $snippet = implode("\n", $snippetTab);

        $template = <<<EOF
        You are a very good PHP developer. Use the following context to find a possible fix for the exception message at the end.
        
File: __FILE__
Exception: __EXCEPTION__
Line: __LINE__

Snippet including line numbers:
__SNIPPET__

EOF;

        return str_replace(
            [
                '__FILE__',
                '__EXCEPTION__',
                '__LINE__',
                '__SNIPPET__',
            ],
            [
                $file,
                $message,
                $line,
                $snippet
            ],
            $template
        );
    }

    public function renderSolution(string $solution)
    {       
        $solution = "<?php\n".$solution;
        $solution = highlight_string($solution, true);

        $solution = nl2br($solution);

        $solution = str_replace(['<span style="color: #0000BB">&lt;?php<br /><br />Fix</span><span style="color: #007700">:<br /></span>'], [''], $solution);

        $template = <<<EOF
        <div class="trace trace-as-html" id="trace-box-0">
        <div class="trace-details">
            <div class="trace-head" style="background-color:var(--color-success)">
                <div class="sf-toggle sf-toggle-on" data-toggle-selector="#trace-html-0" data-toggle-initial="display">
                    <span class="icon icon-close"><svg width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1344 800v64q0 14-9 23t-23 9H480q-14 0-23-9t-9-23v-64q0-14 9-23t23-9h832q14 0 23 9t9 23zm128 448V416q0-66-47-113t-113-47H480q-66 0-113 47t-47 113v832q0 66 47 113t113 47h832q66 0 113-47t47-113zm128-832v832q0 119-84.5 203.5T1312 1536H480q-119 0-203.5-84.5T192 1248V416q0-119 84.5-203.5T480 128h832q119 0 203.5 84.5T1600 416z"></path></svg></span>
                    <span class="icon icon-open"><svg width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1344 800v64q0 14-9 23t-23 9H960v352q0 14-9 23t-23 9h-64q-14 0-23-9t-9-23V896H480q-14 0-23-9t-9-23v-64q0-14 9-23t23-9h352V416q0-14 9-23t23-9h64q14 0 23 9t9 23v352h352q14 0 23 9t9 23zm128 448V416q0-66-47-113t-113-47H480q-66 0-113 47t-47 113v832q0 66 47 113t113 47h832q66 0 113-47t47-113zm128-832v832q0 119-84.5 203.5T1312 1536H480q-119 0-203.5-84.5T192 1248V416q0-119 84.5-203.5T480 128h832q119 0 203.5 84.5T1600 416z"></path></svg></span>
                    <h3 class="trace-class" style="color:var(--tab-background)">Suggested Solution</h3>
                </div>
            </div>
    
            <div id="trace-html-0" class="sf-toggle-content sf-toggle-visible">
                <div id="trace-html-0-0" class="trace-code sf-toggle-content sf-toggle-visible">
                    __SOLUTION__
                </div>        
            </div>
        </div>
        </div>
EOF;
        return str_replace(
            '__SOLUTION__', 
            $solution,
            $template
        );
    }

    /**
     * Returns an excerpt of a code file around the given line number.
     */
    public function fileExcerpt(string $file, int $line, int $srcContext = 3): ?string
    {
        if (is_file($file) && is_readable($file)) {
            // highlight_file could throw warnings
            // see https://bugs.php.net/25725
            $code = @highlight_file($file, true);
            return $this->codeToOlBlock($code, $line);
        }

        return null;
    }

    private function codeToOlBlock(string $code, int $line = 1, int $srcContext = 3)
    {
        // remove main code/span tags
        $code = preg_replace('#^<code.*?>\s*<span.*?>(.*)</span>\s*</code>#s', '\\1', $code);
        // split multiline spans
        $code = preg_replace_callback('#<span ([^>]++)>((?:[^<]*+<br \/>)++[^<]*+)</span>#', function ($m) {
            return "<span $m[1]>".str_replace('<br />', "</span><br /><span $m[1]>", $m[2]).'</span>';
        }, $code);
        $content = explode('<br />', $code);

        $lines = [];
        if (0 > $srcContext) {
            $srcContext = \count($content);
        }

        for ($i = max($line - $srcContext, 1), $max = min($line + $srcContext, \count($content)); $i <= $max; ++$i) {
            $lines[] = '<li'.($i == $line ? ' class="selected"' : '').'><a class="anchor" id="line'.$i.'"></a><code>'.self::fixCodeMarkup($content[$i - 1]).'</code></li>';
        }

        return '<ol start="'.max($line - $srcContext, 1).'">'.implode("\n", $lines).'</ol>';
    }

    private function fixCodeMarkup(string $line)
    {
        // </span> ending tag from previous line
        $opening = strpos($line, '<span');
        $closing = strpos($line, '</span>');
        if (false !== $closing && (false === $opening || $closing < $opening)) {
            $line = substr_replace($line, '', $closing, 7);
        }

        // missing </span> tag at the end of line
        $opening = strrpos($line, '<span');
        $closing = strrpos($line, '</span>');
        if (false !== $opening && (false === $closing || $closing < $opening)) {
            $line .= '</span>';
        }

        return trim($line);
    }
}