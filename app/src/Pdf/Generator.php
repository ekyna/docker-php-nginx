<?php
declare(strict_types=1);

// From https://github.com/tesla-software/chrome2pdf

namespace App\Pdf;

use ChromeDevtoolsProtocol\Context;
use ChromeDevtoolsProtocol\ContextInterface;
use ChromeDevtoolsProtocol\Instance\Launcher;
use ChromeDevtoolsProtocol\Model\Emulation\SetEmulatedMediaRequest;
use ChromeDevtoolsProtocol\Model\Emulation\SetScriptExecutionDisabledRequest;
use ChromeDevtoolsProtocol\Model\Page\NavigateRequest;
use ChromeDevtoolsProtocol\Model\Page\PrintToPDFRequest;
use ChromeDevtoolsProtocol\Model\Page\SetLifecycleEventsEnabledRequest;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Class Generator
 * @package App\Pdf
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Generator
{
    use Attributes;

    /**
     * Context for operations
     */
    private ContextInterface $ctx;

    /**
     * Chrome launcher
     */
    private Launcher $launcher;

    /**
     * Path to temporary html files
     */
    private string $tmpFolderPath;

    /**
     * Path to Chrome binary
     */
    private ?string $chromeExecutablePath = null;

    /**
     * Additional Chrome command line arguments
     */
    private array $chromeArgs = [];

    /**
     * Wait for a given lifecycle event before printing pdf
     */
    private ?string $waitForLifecycleEvent = null;

    /**
     * Whether script execution should be disabled in the page.
     */
    private bool $disableScriptExecution = false;

    /**
     * Web socket connection timeout
     */
    private int $timeout = 10;

    /**
     * Emulates the given media for CSS media queries
     */
    private ?string $emulateMedia = null;

    public function __construct(string $tmpFolderPath)
    {
        $this->tmpFolderPath = rtrim($tmpFolderPath, DIRECTORY_SEPARATOR);
        $this->chromeArgs[] = '--user-data-dir=' . $this->tmpFolderPath;
        $this->launcher = new Launcher();
    }

    public function setBrowserLauncher(Launcher $launcher): Generator
    {
        $this->launcher = $launcher;

        return $this;
    }

    public function setContext(ContextInterface $ctx): Generator
    {
        $this->ctx = $ctx;

        return $this;
    }

    public function appendChromeArgs(array $args): Generator
    {
        $this->chromeArgs = array_unique(array_merge($this->chromeArgs, $args));

        return $this;
    }

    public function setChromeExecutablePath(?string $chromeExecutablePath): Generator
    {
        $this->chromeExecutablePath = $chromeExecutablePath;

        return $this;
    }

    public function setWaitForLifecycleEvent(?string $event): Generator
    {
        $this->waitForLifecycleEvent = $event;

        return $this;
    }

    public function setDisableScriptExecution(bool $disableScriptExecution): Generator
    {
        $this->disableScriptExecution = $disableScriptExecution;

        return $this;
    }

    public function setTimeout(int $timeout): Generator
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setEmulateMedia(?string $emulateMedia): Generator
    {
        $this->emulateMedia = $emulateMedia;

        return $this;
    }

    /**
     * Generate PDF
     */
    public function pdf(): ?string
    {
        $this->ctx = Context::withTimeout(Context::background(), $this->timeout);

        if (!$this->content) {
            throw new InvalidArgumentException('Missing content, set content by calling "setContent($html)" method');
        }

        $launcher = $this->launcher;
        if ($this->chromeExecutablePath) {
            $launcher->setExecutable($this->chromeExecutablePath);
        }

        $ctx = $this->ctx;
        try {
            $instance = $launcher->launch($ctx, ...$this->chromeArgs);
        } catch (Throwable) {
            return null;
        }

        $filename = $this->writeTempFile();
        $pdfOptions = $this->getPDFOptions();

        $pdfResult = null;

        try {
            $tab = $instance->open($ctx);
            $tab->activate($ctx);

            $devtools = $tab->devtools();
            try {
                if ($this->disableScriptExecution) {
                    $devtools->emulation()->setScriptExecutionDisabled(
                        $ctx, SetScriptExecutionDisabledRequest::builder()->setValue(true)->build()
                    );
                }

                if ($this->emulateMedia !== null) {
                    $devtools->emulation()->setEmulatedMedia(
                        $ctx, SetEmulatedMediaRequest::builder()->setMedia($this->emulateMedia)->build()
                    );
                }

                $devtools->page()->enable($ctx);
                $devtools->page()->setLifecycleEventsEnabled(
                    $ctx, SetLifecycleEventsEnabledRequest::builder()->setEnabled(true)->build()
                );
                $devtools->page()->navigate($ctx, NavigateRequest::builder()->setUrl('file://' . $filename)->build());
                $devtools->page()->awaitLoadEventFired($ctx);

                if (null !== $this->waitForLifecycleEvent) {
                    do {
                        $lifecycleEvent = $devtools->page()->awaitLifecycleEvent($ctx)->name;
                    } while ($lifecycleEvent !== $this->waitForLifecycleEvent);
                }

                $response = $devtools->page()->printToPDF($ctx, $pdfOptions);
                $pdfResult = base64_decode($response->data);
            }
            finally {
                $devtools->close();
            }
        }
        finally {
            $instance->close();
        }

        return $pdfResult;
    }

    /**
     * Write content to temporary html file
     *
     * @return string
     */
    protected function writeTempFile(): string
    {
        $filepath = $this->tmpFolderPath;

        $fs = new Filesystem();
        if (!is_dir($filepath)) {
            $fs->mkdir($filepath);
        } elseif (!is_writable($filepath)) {
            throw new RuntimeException(sprintf("Unable to write in directory: %s\n", $filepath));
        }

        $filename = $filepath . DIRECTORY_SEPARATOR . uniqid('chrome2pdf_', true) . '.html';

        file_put_contents($filename, $this->content);

        return $filename;
    }

    /**
     * Populate PDF options
     */
    protected function getPDFOptions(): PrintToPDFRequest
    {
        $pdfOptions = PrintToPDFRequest::make();

        $pdfOptions->landscape = $this->orientation === 'landscape';
        $pdfOptions->marginTop = $this->margins['top'];
        $pdfOptions->marginRight = $this->margins['right'];
        $pdfOptions->marginBottom = $this->margins['bottom'];
        $pdfOptions->marginLeft = $this->margins['left'];
        $pdfOptions->preferCSSPageSize = $this->preferCSSPageSize;
        $pdfOptions->printBackground = $this->printBackground;
        $pdfOptions->scale = $this->scale;
        $pdfOptions->displayHeaderFooter = $this->displayHeaderFooter;

        if ($this->paperWidth) {
            $pdfOptions->paperWidth = $this->paperWidth;
        }

        if ($this->paperHeight) {
            $pdfOptions->paperHeight = $this->paperHeight;
        }

        if ($this->pageRanges) {
            $pdfOptions->pageRanges = $this->pageRanges;
        }

        if ($this->header || $this->footer) {
            if ($this->header === null) {
                $this->header = '<p></p>';
            }

            if ($this->footer === null) {
                $this->footer = '<p></p>';
            }

            $pdfOptions->displayHeaderFooter = true;
            $pdfOptions->headerTemplate = $this->header;
            $pdfOptions->footerTemplate = $this->footer;
        }

        return $pdfOptions;
    }
}
