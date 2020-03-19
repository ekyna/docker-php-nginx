<?php
declare(strict_types=1);

namespace App\Controller;

use App\Pdf\Chrome2Pdf;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function json_decode;

/**
 * Class Controller
 * @author  Etienne Dauvergne <contact@ekyna.com>
 */
class Controller
{
    public function __invoke(Request $request)
    {
        $config = $this->resolveConfig(json_decode($request->getContent(), true));

        if (empty($content = $this->getContent($config))) {
            throw new BadRequestHttpException("You must defined either 'url' or 'html' option.");
        }

        $generated = $this->generate($content, $config);

        return new Response($generated, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function resolveConfig(array $config): array
    {
        $config = array_replace_recursive([
            // Content
            'url'         => null,
            'html'        => null,
            'headers'     => [],
            // Pdf
            'orientation' => 'portrait',
            'format'      => 'A4',
            'paper'       => [
                'width'  => null,
                'height' => null,
                'unit'   => 'in',
            ],
            'margins'     => [
                'top'    => 10,
                'right'  => 10,
                'bottom' => 10,
                'left'   => 10,
                'unit'   => 'in',
            ],
            'header'      => null,
            'footer'      => null,
            'security'    => 1,
        ], $config);

        if (!empty($config['html'])) {
            // Prevent CORS
            $config['security'] = 0;
        }

        return $config;
    }

    private function generate(string $content, array $config): string
    {
        $chrome2Pdf = new Chrome2Pdf();
        $chrome2Pdf
            ->setChromeExecutablePath('/usr/bin/chromium-browser')
            ->setTempFolder(sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid())
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

        if ($config['orientation'] === 'portrait') {
            $chrome2Pdf->portrait();
        } else {
            $chrome2Pdf->landscape();
        }

        $paper = $config['paper'];
        if ($paper['width'] && $paper['height'] && $paper['unit']) {
            $chrome2Pdf
                ->setPaperWidth($paper['width'], $paper['unit'])
                ->setPaperHeight($paper['height'], $paper['unit']);
        } else {
            $chrome2Pdf->setPaperFormat($config['format']);
        }

        $margins = $config['margins'];
        $chrome2Pdf->setMargins(
            $margins['top'],
            $margins['right'],
            $margins['bottom'],
            $margins['left'],
            $margins['unit']
        );

        if ($config['header'] || $config['footer']) {
            $chrome2Pdf
                ->setHeader($config['header'])
                ->setFooter($config['footer'])
                ->setDisplayHeaderFooter(true);
        }

        return $chrome2Pdf
            ->setContent($content)
            ->setWaitForLifecycleEvent('networkIdle')
            ->setPrintBackground(true)
            ->pdf();
    }

    private function getContent(array $config): ?string
    {
        if (!empty($config['html'])) {
            return $config['html'];
        }

        if (is_null($config['url'])) {
            return null;
        }

        $http = new Client();
        try {
            $response = $http->request('GET', $config['url'], [
                'headers' => $config['headers'],
            ]);
        } catch (Exception $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        if (200 !== $code = $response->getStatusCode()) {
            throw new NotFoundHttpException($response->getReasonPhrase());
        }

        return $response->getBody()->getContents();
    }
}
