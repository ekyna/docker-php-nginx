<?php

declare(strict_types=1);

namespace App\Controller;

use App\Pdf\Generator;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

use function base64_decode;
use function json_decode;

/**
 * Class Controller
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class Controller
{
    public function __construct(
        private readonly Generator $generator,
        private readonly string    $token
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->authorize($request);

        if ('json' === $request->getContentType()) {
            $data = (array)json_decode($request->getContent(), true);
        } elseif ('GET' === $request->getMethod()) {
            $data = $request->query->all();
        } elseif ('POST' === $request->getMethod()) {
            $data = $request->request->all();
        } else {
            throw new BadRequestException();
        }

        try {
            $content = $this->generator->generate($data);
        } catch (ExceptionInterface $exception) {
            throw new BadRequestException($exception->getMessage());
        }

        return new Response(base64_decode($content), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function authorize(Request $request): void
    {
        if (!$request->headers->has('X-AUTH-TOKEN')) {
            throw new AccessDeniedHttpException();
        }

        if ($this->token !== $request->headers->get('X-AUTH-TOKEN')) {
            throw new AccessDeniedHttpException();
        }
    }
}
