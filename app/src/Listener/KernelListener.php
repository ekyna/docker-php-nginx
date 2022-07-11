<?php

declare(strict_types=1);

namespace App\Listener;

use HeadlessChromium\Browser;

/**
 * Class KernelListener
 * @package App\Listener
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class KernelListener
{
    public function __construct(private readonly Browser $browser)
    {
    }

    public function __invoke(): void
    {
        $this->browser->close();
    }
}
