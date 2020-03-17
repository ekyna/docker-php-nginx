<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tesla\Chrome2Pdf\Chrome2Pdf;

$request = Request::createFromGlobals();

// Authorization
if ((!$request->server->has('AUTH-TOKEN')) || empty($request->server->get('AUTH-TOKEN'))) {
    $response = new Response('AUTH-TOKEN is not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
} elseif ((!$request->headers->has('X-AUTH-TOKEN')) || empty($request->headers->get('X-AUTH-TOKEN'))) {
    $response = new Response(null, Response::HTTP_FORBIDDEN);
} elseif ($request->headers->get('X-AUTH-TOKEN') !== $request->server->get('AUTH-TOKEN')) {
    $response = new Response(null, Response::HTTP_FORBIDDEN);
} else {
    // Authorized

    // /usr/bin/chromium-browser --headless --no-sandbox --disable-gpu
    // /usr/bin/chromium-browser --headless --disable-gpu --disable-software-rasterizer --disable-dev-shm-usage --no-sandbox

    $config = \json_decode($request->getContent(), true);

    if (isset($config['url']) xor isset($config['html'])) {
        $response = new Response("At least 'url' or 'content' must be defined.", Response::HTTP_BAD_REQUEST);
    } else {
        if (isset($config['url'])) {
            $http = new Client();
            try {
                $response = $http->request('GET',
                    'http://tabstoredev/app_dev.php/admin/commerce/orders/10426/invoices/10143/render.html', [
                        'headers' => [
                            'X-AUTH-TOKEN' => $request,
                        ],
                    ]);
            } catch (\Exception $e) {
                http_response_code(404);
                echo $e->getMessage();
                exit;
            }

            if (200 !== $code = $response->getStatusCode()) {
                http_response_code($code);
                echo $response->getReasonPhrase();
                exit;
            }

            $content = $response->getBody()->getContents();
        }

        $c2p = new Chrome2Pdf();
        $c2p
            ->setChromeExecutablePath('/usr/bin/chromium-browser')
            ->appendChromeArgs([
                '--headless',
                '--disable-gpu',
                '--disable-software-rasterize',
                '--disable-dev-shm-usage',
                '--no-sandbox',
            ]);

        $pdf = $c2p
            ->portrait()
            ->setPaperFormat('A4')
            ->setMargins(6, 6, 6, 6, 'mm')
            ->setContent($content)
            //->setHeader('<div style="font-size: 11px">This is a header</div>')
            //->setFooter('<div style="font-size: 11px">This is a footer <span class="pageNumber"></span>/<span class="totalPages"></span></div>')
            ->pdf();

        header('Content-Type: application/pdf');
        echo $pdf;
    }

}
