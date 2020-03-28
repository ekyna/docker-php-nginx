<?php

namespace App\EventListener;

use App\Pdf\Factory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class KernelListener
 * @package EventListener
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class KernelListener implements EventSubscriberInterface
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * Constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
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
                } catch (\Throwable $e) {

                }
            }

            if (empty($tempFolders)) {
                break;
            }

            usleep(50000);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => ['onTerminate', 0],
        ];
    }
}
