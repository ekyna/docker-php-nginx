<?php

declare(strict_types=1);

namespace App\Command;

use App\Pdf\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function base64_decode;
use function substr;

/**
 * Class GenerateCommand
 * @package App\Command
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class GenerateCommand extends Command
{
    protected static $defaultName = 'app:generate';

    public function __construct(private readonly Generator $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'The URL to generate a PDF from')
            ->addOption('html', null, InputOption::VALUE_REQUIRED, 'The HTML to generate a PDF from');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getOption('url');
        $html = $input->getOption('html');

        if (!(empty($url) xor (empty($html)))) {
            throw new MissingInputException('You must use --url or --html option');
        }

        if (!empty($url)) {
            $content = $this->generator->generate(['url' => $url]);
        } elseif (!empty($html)) {
            $content = $this->generator->generate(['html' => $html]);
        } else {
            throw new MissingInputException();
        }

        $content = base64_decode($content);

        $output->writeln(substr($content, 0, 128) . "\n[...]\n" . substr($content, -128));

        // (Test a previous version of proxy ?)

        return Command::SUCCESS;
    }
}
