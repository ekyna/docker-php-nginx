<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Pdf\Factory;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

/**
 * Class KernelListener
 * @package EventListener
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class KernelListener
{
    public function __construct(private readonly Factory $factory)
    {
    }

    public function onTerminate(): void
    {
        $fs = new Filesystem();
        $tempFolders = $this->factory->getTempFolders();

        while (!empty($tempFolders)) {
            foreach ($tempFolders as $index => $path) {
                if (!$fs->exists($path)) {
                    unset($tempFolders[$index]);
                    continue;
                }

                try {
                    $fs->remove($path);
                    unset($tempFolders[$index]);
                } catch (Throwable) {
                }
            }

            if (empty($tempFolders)) {
                break;
            }

            sleep(2);
        }
    }
}
