<?php

declare(strict_types=1);

// From https://github.com/tesla-software/chrome2pdf

namespace App\Pdf;

use InvalidArgumentException;

/**
 * Trait Attributes
 * @package App\Pdf
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
trait Attributes
{
    /**
     * Pdf content
     */
    private ?string $content = null;

    /**
     * Print background graphics
     */
    private bool $printBackground = false;

    /**
     * Give any CSS @page size declared in the page priority over what is declared
     * in width and height or format options.
     * Defaults to false, which will scale the content to fit the paper size.
     */
    private bool $preferCSSPageSize = false;

    /**
     * Paper orientation
     */
    private string $orientation = 'portrait';

    /**
     * HTML template for the print header. Should be valid HTML markup.
     * Script tags inside templates are not evaluated.
     * Page styles are not visible inside templates.
     *
     * @var string|null
     */
    private ?string $header = null;

    /**
     * HTML template for the print footer. Should be valid HTML markup.
     * Script tags inside templates are not evaluated.
     * Page styles are not visible inside templates.
     */
    private ?string $footer = null;

    /**
     * Paper width in inches
     */
    private float $paperWidth = 8.27;

    /**
     * Paper height in inches
     */
    private float $paperHeight = 11.7;

    /**
     * Page margins in inches
     */
    private array $margins = [
        'top'    => 0.4,
        'right'  => 0.4,
        'bottom' => 0.4,
        'left'   => 0.4,
    ];

    /**
     * Default paper formats
     */
    private array $paperFormats = [
        'letter'  => [8.5, 11],
        'a0'      => [33.1, 46.8],
        'a1'      => [23.4, 33.1],
        'a2'      => [16.54, 23.4],
        'a3'      => [11.7, 16.54],
        'a4'      => [8.27, 11.7],
        'a5'      => [5.83, 8.27],
        'a6'      => [4.13, 5.83],
        'legal'   => [8.5, 14],
        'tabloid' => [11, 17],
        'ledger'  => [17, 11],
    ];

    /**
     * Used for converting measurement units
     * Inspired by https://github.com/GoogleChrome/puppeteer
     */
    private array $unitToPixels = [
        'px' => 1,
        'in' => 96,
        'cm' => 37.8,
        'mm' => 3.78,
    ];

    /**
     * Scale of the webpage rendering.
     * Scale amount must be between 0.1 and 2.
     */
    private float $scale = 1;

    /**
     * Display header and footer.
     */
    private bool $displayHeaderFooter = false;

    /**
     * Paper ranges to print, e.g., '1-5, 8, 11-13'.
     * By default prints all pages.
     */
    private ?string $pageRanges = null;

    public function setPaperFormat(string $format): self
    {
        $format = mb_strtolower($format);

        if (!array_key_exists($format, $this->paperFormats)) {
            throw new InvalidArgumentException('Paper format "' . $format . '" does not exist');
        }

        $this->paperWidth = $this->paperFormats[$format][0];
        $this->paperHeight = $this->paperFormats[$format][1];

        return $this;
    }

    public function portrait(): self
    {
        $this->orientation = 'portrait';

        return $this;
    }

    public function landscape(): self
    {
        $this->orientation = 'landscape';

        return $this;
    }

    public function setMargins(float $top, float $right, float $bottom, float $left, string $unit = 'in'): self
    {
        $top = $this->convertToInches($top, $unit);
        $right = $this->convertToInches($right, $unit);
        $bottom = $this->convertToInches($bottom, $unit);
        $left = $this->convertToInches($left, $unit);

        $this->margins['top'] = $top;
        $this->margins['right'] = $right;
        $this->margins['bottom'] = $bottom;
        $this->margins['left'] = $left;

        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function setHeader(?string $header): self
    {
        $this->header = $header;

        return $this;
    }

    public function setFooter(?string $footer): self
    {
        $this->footer = $footer;

        return $this;
    }

    public function setPreferCSSPageSize(bool $preferCss): self
    {
        $this->preferCSSPageSize = $preferCss;

        return $this;
    }

    public function setPaperWidth(float $width, string $unit = 'in'): self
    {
        $this->paperWidth = $this->convertToInches($width, $unit);

        return $this;
    }

    public function setPaperHeight(float $height, string $unit = 'in'): self
    {
        $this->paperHeight = $this->convertToInches($height, $unit);

        return $this;
    }

    public function setScale(float $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    public function setDisplayHeaderFooter(bool $displayHeaderFooter): self
    {
        $this->displayHeaderFooter = $displayHeaderFooter;

        return $this;
    }

    public function setPrintBackground(bool $printBg): self
    {
        $this->printBackground = $printBg;

        return $this;
    }

    public function setPageRanges(?string $pageRanges): self
    {
        $this->pageRanges = $pageRanges;

        return $this;
    }

    protected function convertToInches(float $value, string $unit): float
    {
        $unit = mb_strtolower($unit);

        if (!array_key_exists($unit, $this->unitToPixels)) {
            throw new InvalidArgumentException('Unknown measurement unit "' . $unit . '"');
        }

        return ($value * $this->unitToPixels[$unit]) / 96;
    }
}
