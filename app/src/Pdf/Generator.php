<?php

declare(strict_types=1);

namespace App\Pdf;

use HeadlessChromium\Browser;
use RuntimeException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Generator
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class Generator
{
    private const INCHES      = 'in';
    private const MILLIMETERS = 'mm';

    private const MM_TO_INCHES = 0.0393701;

    private ?OptionsResolver $resolver = null;

    public function __construct(private readonly Browser $browser)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function generate(array $options): string
    {
        $options = $this->resolveOptions($options);

        $page = $this->browser->createPage();

        if (!empty($url = $options['url'])) {
            $page->navigate($url)->waitForNavigation();
        } elseif (!empty($html = $options['html'])) {
            $page->setHtml($html);
        } else {
            throw new RuntimeException('Expected URL or HTML.');
        }

        // Remove unexpected options
        unset($options['url'], $options['html'], $options['unit']);

        return $page->pdf($options)->getBase64();
    }

    /**
     * @throws ExceptionInterface
     */
    private function resolveOptions(array $options): array
    {
        $options = $this->getOptionsResolver()->resolve($options);

        if (self::MILLIMETERS === $options['unit']) {
            // Convert millimeters into inches
            $keys = [
                'marginTop',
                'marginBottom',
                'marginLeft',
                'marginRight',
                'paperWidth',
                'paperHeight',
            ];
            foreach ($keys as $key) {
                $options[$key] = $options[$key] * self::MM_TO_INCHES;
            }
        }

        return $options;
    }

    private function getOptionsResolver(): OptionsResolver
    {
        if (null !== $this->resolver) {
            return $this->resolver;
        }

        // @see https://github.com/chrome-php/chrome#print-as-pdf
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url'                 => null,
            'html'                => null,
            // ----
            'headerTemplate'      => '',
            'footerTemplate'      => '',
            // ---
            'landscape'           => false,
            'printBackground'     => true,
            'displayHeaderFooter' => false,
            'preferCSSPageSize'   => false, // reads parameters directly from @page
            // ---
            'unit'                => self::INCHES,
            'marginTop'           => 0.4,
            'marginBottom'        => 0.4,
            'marginLeft'          => 0.4,
            'marginRight'         => 0.4,
            'paperWidth'          => 8.2677,
            'paperHeight'         => 11.6929,
            'scale'               => 1.0,
        ]);

        $resolver->setAllowedTypes('url', ['string', 'null']);
        $resolver->setAllowedTypes('html', ['string', 'null']);

        $resolver->setAllowedTypes('headerTemplate', 'string');
        $resolver->setAllowedTypes('footerTemplate', 'string');

        $resolver->setAllowedTypes('landscape', 'bool');
        $resolver->setAllowedTypes('printBackground', 'bool');
        $resolver->setAllowedTypes('displayHeaderFooter', 'bool');
        $resolver->setAllowedTypes('preferCSSPageSize', 'bool');

        $resolver->setAllowedTypes('unit', 'string');
        $resolver->setAllowedTypes('marginTop', ['int', 'float']);
        $resolver->setAllowedTypes('marginBottom', ['int', 'float']);
        $resolver->setAllowedTypes('marginLeft', ['int', 'float']);
        $resolver->setAllowedTypes('marginRight', ['int', 'float']);
        $resolver->setAllowedTypes('paperWidth', ['int', 'float']);
        $resolver->setAllowedTypes('paperHeight', ['int', 'float']);
        $resolver->setAllowedTypes('scale', ['int', 'float']);

        $resolver->setAllowedValues('unit', [self::MILLIMETERS, self::INCHES]);

        return $this->resolver = $resolver;
    }
}
