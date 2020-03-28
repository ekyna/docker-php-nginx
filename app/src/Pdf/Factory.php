<?php
declare(strict_types=1);

namespace App\Pdf;

/**
 * Class Factory
 * @package Pdf
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class Factory
{
    /**
     * @var array
     */
    private $tempFolders = [];

    /**
     * Returns the tempFolders.
     *
     * @return array
     */
    public function getTempFolders(): array
    {
        return $this->tempFolders;
    }

    public function create(): Generator
    {
        $this->tempFolders[] = $tempFolder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();

        $generator = new Generator($tempFolder);
        $generator
            ->setChromeExecutablePath('/usr/bin/chromium-browser')
            ->setWaitForLifecycleEvent('networkIdle')
            ->setPrintBackground(true)
            ->appendChromeArgs([
                // https://peter.sh/experiments/chromium-command-line-switches/
                '--headless',
                '--no-sandbox',
                '--no-zygote',
                '--allow-http-background-page',
                '--disable-setuid-sandbox',
                '--disable-gpu',
                '--disable-software-rasterize',
                '--disable-dev-shm-usage',
                '--disable-gl-drawing-for-tests',
                '--disable-canvas-aa',
                '--disable-2d-canvas-clip-aa',
                '--use-gl=desktop',
                '--enable-webgl',
                '--incognito',
                '--disable-audio-output',
                '--no-first-run',
                '--not-pings',
                '--disable-infobars',
                '--disable-breakpad',
                '--disable-web-security',
            ]);

        return $generator;
    }
}
